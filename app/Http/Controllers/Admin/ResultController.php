<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\ResponseAnswer;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function summary(Form $form)
    {
        $form->load('fields');
        $total = $form->responses()->count();
        $answers = ResponseAnswer::query()
            ->whereIn('form_field_id', $form->fields->pluck('id'))
            ->with('response:id,respondent_name,respondent_unit,is_test,created_at')
            ->get()
            ->groupBy('form_field_id');

        $summaries = [];
        foreach ($form->fields as $field) {
            if ($field->isAnswerable()) {
                $summaries[$field->id] = $this->summarize($field, $answers->get($field->id, collect()), $total);
            }
        }

        return view('admin.forms.results', [
            'form' => $form,
            'total' => $total,
            'summaries' => $summaries,
            'last' => $form->responses()->latest()->first(),
            'testCount' => $form->responses()->where('is_test', true)->count(),
        ]);
    }

    public function destroyTests(Form $form): RedirectResponse
    {
        $deleted = $form->responses()->where('is_test', true)->delete();

        return back()->with('status', $deleted.' maklum balas ujian dipadam.');
    }

    public function responses(Form $form)
    {
        $responses = $form->responses()->latest()->paginate(20);

        return view('admin.forms.responses', compact('form', 'responses'));
    }

    public function show(Form $form, FormResponse $response)
    {
        abort_unless($response->form_id === $form->id, 404);
        $form->load('fields');
        $answers = $response->answers->keyBy('form_field_id');

        return view('admin.forms.response', compact('form', 'response', 'answers'));
    }

    public function destroy(Form $form, FormResponse $response): RedirectResponse
    {
        abort_unless($response->form_id === $form->id, 404);
        $response->delete();

        return redirect()->route('admin.forms.responses', $form)->with('status', 'Maklum balas dipadam.');
    }

    public function export(Form $form): StreamedResponse
    {
        $form->load('fields');
        $fields = $form->fields->filter->isAnswerable();

        $header = ['ID', 'Tarikh', 'Nama', 'Unit'];
        foreach ($fields as $f) {
            if ($f->type === 'part_feedback') {
                foreach ($f->options ?? [] as $o) {
                    $header[] = $f->label.' – '.$o['label'].' (status)';
                    $header[] = $f->label.' – '.$o['label'].' (komen)';
                }
            } else {
                $header[] = $f->label;
                if ($f->type === 'image_choice' && $f->setting('allow_comment')) {
                    $header[] = $f->label.' (komen)';
                }
            }
        }

        $filename = 'maklum-balas-'.$form->slug.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($form, $fields, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel baca UTF-8
            fputcsv($out, $header);

            $form->responses()->with('answers')->orderBy('id')->chunk(200, function ($chunk) use ($out, $fields) {
                foreach ($chunk as $r) {
                    $by = $r->answers->keyBy('form_field_id');
                    $row = [$r->id, $r->created_at->format('Y-m-d H:i'), $r->respondent_name, $r->respondent_unit];
                    foreach ($fields as $f) {
                        $a = $by->get($f->id);
                        if ($f->type === 'part_feedback') {
                            foreach ($f->options ?? [] as $o) {
                                $p = $a->value[$o['value']] ?? null;
                                $row[] = $p ? (FormField::PART_STATUSES[$p['status'] ?? ''] ?? '') : '';
                                $row[] = $p['comment'] ?? '';
                            }

                            continue;
                        }
                        $row[] = $a ? self::plain($f, $a->value) : '';
                        if ($f->type === 'image_choice' && $f->setting('allow_comment')) {
                            $row[] = $a->comment ?? '';
                        }
                    }
                    // elak formula injection dalam Excel
                    $row = array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $row);
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function plain(FormField $f, $value): string
    {
        return match ($f->type) {
            'single_choice', 'dropdown', 'image_choice' => $f->optionLabel((string) $value),
            'multi_choice' => collect((array) $value)->map(fn ($v) => $f->optionLabel($v))->implode(', '),
            'rating' => $value.'/'.$f->maxRating(),
            default => (string) $value,
        };
    }

    private function summarize(FormField $field, $answers, int $total): array
    {
        $n = $answers->count();
        $base = ['answered' => $n, 'total' => $total];

        switch ($field->type) {
            case 'single_choice':
            case 'dropdown':
            case 'image_choice':
            case 'multi_choice':
                $counts = array_fill_keys(array_keys($field->optionMap()), 0);
                $comments = [];
                foreach ($answers as $a) {
                    foreach ((array) $a->value as $v) {
                        if (isset($counts[$v])) {
                            $counts[$v]++;
                        }
                        if ($a->comment) {
                            $comments[$v][] = ['who' => $a->response, 'text' => $a->comment];
                        }
                    }
                }
                $max = max(1, max($counts ?: [0]));
                $rows = [];
                foreach ($field->optionMap() as $key => $opt) {
                    $rows[] = [
                        'option' => $opt,
                        'count' => $counts[$key],
                        'pct' => $n ? round($counts[$key] / $n * 100) : 0,
                        'bar' => round($counts[$key] / $max * 100),
                        'comments' => $comments[$key] ?? [],
                        'leader' => $counts[$key] === max($counts) && $counts[$key] > 0,
                    ];
                }

                return $base + ['kind' => 'choice', 'rows' => $rows];

            case 'rating':
                $dist = array_fill(1, $field->maxRating(), 0);
                foreach ($answers as $a) {
                    if (isset($dist[(int) $a->value])) {
                        $dist[(int) $a->value]++;
                    }
                }

                return $base + [
                    'kind' => 'rating',
                    'avg' => $n ? round($answers->avg(fn ($a) => (int) $a->value), 2) : null,
                    'dist' => $dist,
                    'max' => $field->maxRating(),
                ];

            case 'part_feedback':
                $rows = [];
                foreach ($field->optionMap() as $key => $opt) {
                    $r = ['option' => $opt, 'counts' => array_fill_keys(array_keys(FormField::PART_STATUSES), 0), 'comments' => []];
                    foreach ($answers as $a) {
                        $p = $a->value[$key] ?? null;
                        if (! $p) {
                            continue;
                        }
                        if (! empty($p['status']) && isset($r['counts'][$p['status']])) {
                            $r['counts'][$p['status']]++;
                        }
                        if (! empty($p['comment'])) {
                            $r['comments'][] = ['who' => $a->response, 'status' => $p['status'] ?? null, 'text' => $p['comment']];
                        }
                    }
                    $r['total'] = array_sum($r['counts']);
                    $rows[] = $r;
                }

                return $base + ['kind' => 'parts', 'rows' => $rows];

            default:
                return $base + ['kind' => 'text', 'items' => $answers->map(fn ($a) => ['who' => $a->response, 'text' => (string) $a->value])->values()->all()];
        }
    }
}
