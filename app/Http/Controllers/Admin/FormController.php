<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function index()
    {
        $forms = Form::withCount(['responses', 'fields'])->latest()->get();

        return view('admin.forms.index', compact('forms'));
    }

    public function create()
    {
        $form = new Form([
            'status' => 'draft',
            'collect_identity' => true,
            'identity_required' => false,
            'allow_multiple' => false,
        ]);

        return view('admin.forms.builder', [
            'form' => $form,
            'payload' => $this->payload($form, collect()),
            'responseCount' => 0,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $form = DB::transaction(function () use ($data, $request) {
            $form = Form::create(array_merge($data['form'], [
                'user_id' => $request->user()->id,
                'slug' => Form::uniqueSlug($data['form']['title']),
            ]));
            $this->syncFields($form, $data['fields']);

            return $form;
        });

        session()->flash('status', 'Borang berjaya dicipta.');

        return response()->json(['redirect' => route('admin.forms.results', $form)]);
    }

    public function edit(Form $form)
    {
        return view('admin.forms.builder', [
            'form' => $form,
            'payload' => $this->payload($form, $form->fields),
            'responseCount' => $form->responses()->count(),
        ]);
    }

    public function update(Request $request, Form $form): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $form) {
            $form->update($data['form']);
            $this->syncFields($form, $data['fields']);
        });

        session()->flash('status', 'Perubahan borang disimpan.');

        return response()->json(['redirect' => route('admin.forms.results', $form)]);
    }

    public function destroy(Form $form): RedirectResponse
    {
        $form->delete();

        return redirect()->route('admin.forms.index')->with('status', 'Borang telah dipadam.');
    }

    public function duplicate(Form $form): RedirectResponse
    {
        $copy = DB::transaction(function () use ($form) {
            $copy = $form->replicate(['slug']);
            $copy->title = $form->title.' (salinan)';
            $copy->slug = Form::uniqueSlug($form->title);
            $copy->status = 'draft';
            $copy->save();

            foreach ($form->fields as $field) {
                $copy->fields()->create($field->only(['type', 'label', 'help', 'required', 'options', 'settings', 'position']));
            }

            return $copy;
        });

        return redirect()->route('admin.forms.edit', $copy)->with('status', 'Salinan borang dicipta (draf).');
    }

    public function setStatus(Request $request, Form $form): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:draft,open,closed']]);
        $form->update(['status' => $request->status]);

        return back()->with('status', 'Status borang dikemas kini: '.$form->fresh()->statusLabel().'.');
    }

    private function payload(Form $form, $fields): array
    {
        return [
            'form' => [
                'title' => $form->title ?? '',
                'description' => $form->description ?? '',
                'status' => $form->status ?? 'draft',
                'closes_at' => $form->closes_at?->format('Y-m-d\TH:i') ?? '',
                'collect_identity' => (bool) $form->collect_identity,
                'identity_required' => (bool) $form->identity_required,
                'respondent_options' => implode("\n", $form->respondent_options ?? []),
                'allow_multiple' => (bool) $form->allow_multiple,
                'thank_you' => $form->thank_you ?? '',
            ],
            'fields' => $fields->map(fn (FormField $f) => [
                'id' => $f->id,
                'type' => $f->type,
                'label' => $f->label,
                'help' => $f->help ?? '',
                'required' => (bool) $f->required,
                'options' => $f->options ?? [],
                'settings' => (object) ($f->settings ?? []),
            ])->values()->all(),
        ];
    }

    private function validated(Request $request): array
    {
        $types = array_keys(FormField::TYPES);

        $validated = $request->validate([
            'form.title' => ['required', 'string', 'max:200'],
            'form.description' => ['nullable', 'string', 'max:5000'],
            'form.status' => ['required', 'in:draft,open,closed'],
            'form.closes_at' => ['nullable', 'date'],
            'form.collect_identity' => ['boolean'],
            'form.identity_required' => ['boolean'],
            'form.respondent_options' => ['nullable', 'string', 'max:10000'],
            'form.allow_multiple' => ['boolean'],
            'form.thank_you' => ['nullable', 'string', 'max:2000'],
            'fields' => ['array', 'max:100'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.type' => ['required', 'in:'.implode(',', $types)],
            'fields.*.label' => ['required', 'string', 'max:500'],
            'fields.*.help' => ['nullable', 'string', 'max:2000'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:60'],
            'fields.*.options.*.value' => ['nullable', 'string', 'max:20'],
            'fields.*.options.*.label' => ['nullable', 'string', 'max:300'],
            'fields.*.options.*.description' => ['nullable', 'string', 'max:1000'],
            'fields.*.options.*.image' => ['nullable', 'string', 'max:500'],
            'fields.*.options.*.gallery' => ['nullable', 'array', 'max:30'],
            'fields.*.options.*.gallery.*.src' => ['nullable', 'string', 'max:500'],
            'fields.*.options.*.gallery.*.caption' => ['nullable', 'string', 'max:200'],
            'fields.*.settings' => ['nullable', 'array'],
        ], [
            'form.title.required' => 'Tajuk borang diperlukan.',
            'fields.*.label.required' => 'Setiap soalan perlu ada tajuk/label.',
        ]);

        $form = Arr::only($validated['form'], [
            'title', 'description', 'status', 'closes_at', 'collect_identity', 'identity_required', 'respondent_options', 'allow_multiple', 'thank_you',
        ]);
        // Satu baris = satu nama
        $form['respondent_options'] = collect(preg_split('/\R/u', (string) ($form['respondent_options'] ?? '')))
            ->map(fn ($n) => trim($n))->filter()->unique()->values()->all() ?: null;
        $form['closes_at'] = ! empty($form['closes_at']) ? $form['closes_at'] : null;
        $form['identity_required'] = ($form['collect_identity'] ?? false) && ($form['identity_required'] ?? false);

        $fields = [];
        foreach ($validated['fields'] ?? [] as $i => $f) {
            $hasOptions = in_array($f['type'], ['single_choice', 'multi_choice', 'dropdown', 'image_choice', 'part_feedback'], true);
            $options = [];
            if ($hasOptions) {
                foreach ($f['options'] ?? [] as $opt) {
                    $label = trim($opt['label'] ?? '');
                    if ($label === '') {
                        continue;
                    }
                    $gallery = collect($opt['gallery'] ?? [])
                        ->filter(fn ($g) => ! empty($g['src']))
                        ->map(fn ($g) => ['src' => $g['src'], 'caption' => $g['caption'] ?? ''])
                        ->values()->all();
                    $options[] = [
                        // kekalkan kunci asal supaya jawapan lama kekal sah
                        'value' => ! empty($opt['value']) && preg_match('/^[a-z0-9]{4,12}$/', $opt['value']) ? $opt['value'] : Str::lower(Str::random(8)),
                        'label' => $label,
                        'description' => $opt['description'] ?? '',
                        'image' => $opt['image'] ?? '',
                        'gallery' => $gallery,
                    ];
                }
            }

            $settings = [];
            $raw = $f['settings'] ?? [];
            if ($f['type'] === 'image_choice') {
                $settings['allow_comment'] = (bool) ($raw['allow_comment'] ?? false);
                $settings['comment_label'] = Str::limit($raw['comment_label'] ?? '', 200, '');
            }
            if ($f['type'] === 'rating') {
                $settings['max'] = max(3, min(10, (int) ($raw['max'] ?? 5)));
            }
            // Soalan bersyarat: papar hanya jika pilihan (kunci) ini dipilih pada soalan lain
            if (is_string($raw['show_if'] ?? null) && preg_match('/^[a-z0-9]{4,12}$/', $raw['show_if'])) {
                $settings['show_if'] = $raw['show_if'];
            }

            $fields[] = [
                'id' => $f['id'] ?? null,
                'type' => $f['type'],
                'label' => $f['label'],
                'help' => $f['help'] ?? null,
                'required' => $f['type'] === 'heading' ? false : (bool) ($f['required'] ?? false),
                'options' => $hasOptions ? $options : null,
                'settings' => $settings ?: null,
                'position' => $i,
            ];
        }

        return ['form' => $form, 'fields' => $fields];
    }

    private function syncFields(Form $form, array $fields): void
    {
        $existing = $form->fields()->get()->keyBy('id');
        $keep = [];

        foreach ($fields as $data) {
            $id = $data['id'];
            unset($data['id']);

            if ($id && $existing->has($id)) {
                $existing[$id]->update($data);
                $keep[] = $id;
            } else {
                $keep[] = $form->fields()->create($data)->id;
            }
        }

        $form->fields()->whereNotIn('id', $keep)->delete();
    }
}
