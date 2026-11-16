<?php

namespace App\Http\Controllers;

use App\Mail\NewFormResponseMail;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

use function Illuminate\Support\defer;

class PublicFormController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        // Pratonton oleh admin (draf) dibenarkan jika sudah log masuk.
        $preview = $request->user() && $form->status === 'draft';

        if (! $form->isAcceptingResponses() && ! $preview) {
            return response()->view('public.closed', compact('form'), $form->status === 'draft' ? 404 : 200);
        }

        if ($this->alreadySubmitted($request, $form) && ! $preview) {
            return view('public.thanks', ['form' => $form, 'already' => true]);
        }

        return view('public.show', [
            'form' => $form,
            'fields' => $form->fields,
            'preview' => $preview,
        ]);
    }

    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = Form::where('slug', $slug)->with('fields')->firstOrFail();

        // Mod ujian: admin yang log masuk boleh menghantar pada borang draf; hantaran ditanda sebagai ujian.
        $isTest = $request->user() && $form->status === 'draft';

        if (! $form->isAcceptingResponses() && ! $isTest) {
            return redirect()->route('form.show', $form->slug);
        }
        if (! $isTest && $this->alreadySubmitted($request, $form)) {
            return redirect()->route('form.thanks', $form->slug);
        }

        [$clean, $errors] = $this->collect($request, $form);

        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }

        $response = DB::transaction(function () use ($form, $request, $clean, $isTest) {
            $response = FormResponse::create([
                'form_id' => $form->id,
                'is_test' => $isTest,
                'respondent_name' => $form->collect_identity ? trim((string) $request->input('respondent_name')) ?: null : null,
                'respondent_unit' => $form->collect_identity && ! $form->respondent_options ? trim((string) $request->input('respondent_unit')) ?: null : null,
            ]);

            foreach ($clean as $fieldId => $row) {
                $response->answers()->create([
                    'form_field_id' => $fieldId,
                    'value' => $row['value'],
                    'comment' => $row['comment'],
                ]);
            }

            return $response;
        });

        if (! $isTest) {
            $this->notifyNewResponse($response);
        }

        $redirect = redirect()->route('form.thanks', $form->slug);

        if (! $form->allow_multiple && ! $isTest) {
            $redirect->withCookie(cookie('submitted_'.$form->id, '1', 60 * 24 * 365));
        }

        return $redirect->with('just_submitted', true)->with('test_submission', $isTest);
    }

    public function thanks(Request $request, string $slug)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        return view('public.thanks', [
            'form' => $form,
            'already' => ! $request->session()->has('just_submitted'),
            'test' => (bool) $request->session()->get('test_submission') && $request->user(),
        ]);
    }

    /**
     * E-mel kepada admin selepas respons dihantar ke pelayar, supaya responden tidak menunggu SMTP.
     * Kegagalan e-mel hanya dilog dan tidak menjejaskan maklum balas yang telah disimpan.
     */
    private function notifyNewResponse(FormResponse $response): void
    {
        $recipients = config('mail.notify_to');

        if (! $recipients) {
            return;
        }

        defer(function () use ($response, $recipients) {
            try {
                Mail::to($recipients)->send(new NewFormResponseMail($response));
            } catch (Throwable $e) {
                Log::warning('Notifikasi e-mel maklum balas gagal dihantar.', [
                    'form_response_id' => $response->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    private function alreadySubmitted(Request $request, Form $form): bool
    {
        return ! $form->allow_multiple && $request->cookie('submitted_'.$form->id) === '1';
    }

    /**
     * Sahkan input & hasilkan jawapan bersih.
     *
     * @return array{0: array<int, array{value: mixed, comment: ?string}>, 1: array<string, string>}
     */
    private function collect(Request $request, Form $form): array
    {
        $errors = [];
        $clean = [];

        if ($form->collect_identity) {
            $name = trim((string) $request->input('respondent_name'));
            $nameOptions = $form->respondent_options ?? [];
            if ($form->identity_required && $name === '') {
                $errors['respondent_name'] = $nameOptions ? 'Sila pilih nama anda.' : 'Sila isi nama anda.';
            } elseif ($nameOptions && $name !== '' && ! in_array($name, $nameOptions, true)) {
                $errors['respondent_name'] = 'Nama tidak sah. Sila pilih daripada senarai.';
            }
            if (mb_strlen($name) > 150 || mb_strlen((string) $request->input('respondent_unit')) > 150) {
                $errors['respondent_name'] = 'Nama/unit terlalu panjang.';
            }
        }

        $answers = (array) $request->input('answers', []);
        $comments = (array) $request->input('comments', []);

        // Pilihan yang dipilih (untuk soalan bersyarat)
        $picked = [];
        foreach ($form->fields as $field) {
            if (in_array($field->type, ['single_choice', 'dropdown', 'image_choice', 'multi_choice'], true)) {
                foreach ((array) ($answers[$field->id] ?? []) as $v) {
                    if (is_string($v) && array_key_exists($v, $field->optionMap())) {
                        $picked[] = $v;
                    }
                }
            }
        }

        foreach ($form->fields as $field) {
            if (! $field->isAnswerable()) {
                continue;
            }
            // Soalan bersyarat yang tidak dipaparkan diabaikan sepenuhnya
            if (($cond = $field->setting('show_if')) && ! in_array($cond, $picked, true)) {
                continue;
            }

            $key = 'answers.'.$field->id;
            $raw = $answers[$field->id] ?? null;
            $comment = null;
            $value = null;
            $filled = false;

            switch ($field->type) {
                case 'short_text':
                case 'long_text':
                    $value = is_string($raw) ? trim($raw) : '';
                    $filled = $value !== '';
                    if (mb_strlen($value) > ($field->type === 'short_text' ? 255 : 5000)) {
                        $errors[$key] = 'Jawapan terlalu panjang.';
                    }
                    break;

                case 'single_choice':
                case 'dropdown':
                case 'image_choice':
                    $value = is_string($raw) ? $raw : '';
                    $filled = $value !== '';
                    if ($filled && ! array_key_exists($value, $field->optionMap())) {
                        $errors[$key] = 'Pilihan tidak sah.';
                    }
                    if ($field->type === 'image_choice' && $field->setting('allow_comment')) {
                        $c = $comments[$field->id] ?? '';
                        $comment = is_string($c) && trim($c) !== '' ? mb_substr(trim($c), 0, 3000) : null;
                    }
                    break;

                case 'multi_choice':
                    $value = array_values(array_filter((array) $raw, 'is_string'));
                    $filled = count($value) > 0;
                    if (array_diff($value, array_keys($field->optionMap()))) {
                        $errors[$key] = 'Pilihan tidak sah.';
                    }
                    break;

                case 'rating':
                    $filled = is_numeric($raw) && $raw !== '';
                    $value = $filled ? (int) $raw : null;
                    if ($filled && ($value < 1 || $value > $field->maxRating())) {
                        $errors[$key] = 'Penilaian tidak sah.';
                    }
                    break;

                case 'part_feedback':
                    $value = [];
                    $missing = 0;
                    $needComment = 0;
                    foreach ($field->optionMap() as $optKey => $opt) {
                        $row = is_array($raw[$optKey] ?? null) ? $raw[$optKey] : [];
                        $status = is_string($row['status'] ?? null) ? $row['status'] : '';
                        $c = is_string($row['comment'] ?? null) ? trim($row['comment']) : '';
                        if ($status !== '' && ! array_key_exists($status, FormField::PART_STATUSES)) {
                            $status = '';
                        }
                        if ($status === '' && $c === '') {
                            $missing++;

                            continue;
                        }
                        if ($status === '') {
                            $missing++;
                        }
                        if ($status === 'ubah' && $c === '') {
                            $needComment++;
                        }
                        $value[$optKey] = ['status' => $status ?: null, 'comment' => mb_substr($c, 0, 2000)];
                    }
                    $filled = count($value) > 0;
                    if ($needComment) {
                        $errors[$key] = 'Sila nyatakan apa yang perlu ditambah baik pada bahagian yang ditanda "Perlu penambahbaikan".';
                    } elseif ($field->required && $missing > 0) {
                        $errors[$key] = 'Sila beri penilaian untuk semua bahagian.';
                    }
                    break;
            }

            if ($field->required && ! $filled && ! isset($errors[$key])) {
                $errors[$key] = 'Soalan ini wajib dijawab.';
            }

            if ($filled) {
                $clean[$field->id] = ['value' => $value, 'comment' => $comment];
            }
        }

        if (! $errors && ! $clean) {
            $errors['form'] = 'Sila jawab sekurang-kurangnya satu soalan.';
        }

        return [$clean, $errors];
    }
}
