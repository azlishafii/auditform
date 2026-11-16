@extends('layouts.public')
@section('title', $form->title)

@section('content')
@if ($preview)
    <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <strong>Mod ujian (draf).</strong> Borang ini belum dibuka kepada responden. Anda boleh mengisi dan menghantar untuk menguji; hantaran ditanda sebagai <strong>ujian</strong> dan boleh dipadam di halaman keputusan.
    </div>
@endif

<header class="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
    <h1 class="font-display text-2xl font-bold leading-snug text-slate-900 sm:text-3xl">{{ $form->title }}</h1>
    @if ($form->description)
        <p class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-slate-600">{{ $form->description }}</p>
    @endif
    @if ($form->closes_at)
        <p class="mt-4 inline-block rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Tutup pada {{ $form->closes_at->translatedFormat('j M Y, g:i A') }}</p>
    @endif
</header>

@php
    // Pilihan semasa (untuk soalan bersyarat), dipulihkan selepas ralat pengesahan
    $picks = [];
    foreach ($fields as $pf) {
        if (in_array($pf->type, ['single_choice', 'dropdown', 'image_choice', 'multi_choice'], true) && old('answers.'.$pf->id)) {
            $picks[$pf->id] = old('answers.'.$pf->id);
        }
    }
    $nameOptions = $form->respondent_options ?? [];
@endphp

<form method="POST" action="{{ route('form.submit', $form->slug) }}" class="space-y-5" novalidate
      x-data="{ busy: false, picks: @js((object) $picks), has(v) { return Object.values(this.picks).flat().includes(v) } }" @submit="busy = true">
    @csrf

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" id="form-errors">
            <strong>Sila semak semula borang anda.</strong>
            @if ($errors->has('form'))<div>{{ $errors->first('form') }}</div>@endif
            <span class="block text-red-600/80">Medan bermasalah ditanda merah di bawah.</span>
        </div>
    @endif

    @if ($form->collect_identity)
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if ($nameOptions)
                <label class="label" for="respondent_name">Nama @if ($form->identity_required)<span class="text-red-500">*</span>@endif</label>
                <select id="respondent_name" name="respondent_name" class="input text-base @error('respondent_name') border-red-400 @enderror">
                    <option value="">— Pilih nama anda —</option>
                    @foreach ($nameOptions as $n)
                        <option value="{{ $n }}" @selected(old('respondent_name') === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                @error('respondent_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="respondent_name">Nama @if ($form->identity_required)<span class="text-red-500">*</span>@endif</label>
                    <input id="respondent_name" name="respondent_name" value="{{ old('respondent_name') }}" maxlength="150" autocomplete="name" class="input @error('respondent_name') border-red-400 @enderror">
                    @error('respondent_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="respondent_unit">Jawatan / Unit</label>
                    <input id="respondent_unit" name="respondent_unit" value="{{ old('respondent_unit') }}" maxlength="150" class="input">
                </div>
            </div>
            @endif
        </section>
    @endif

    @foreach ($fields as $field)
        @php
            $err = $errors->first('answers.'.$field->id); $fid = $field->id;
            // show_if disahkan [a-z0-9] semasa simpan, selamat untuk dicetak terus
            $cond = $field->setting('show_if');
            $showAttr = $cond && preg_match('/^[a-z0-9]{4,12}$/', $cond) ? 'x-show="has(\''.$cond.'\')" x-cloak x-transition.opacity' : '';
        @endphp

        @if ($field->type === 'heading')
            <div class="px-1 pt-4" {!! $showAttr !!}>
                <h2 class="text-lg font-semibold text-slate-900">{{ $field->label }}</h2>
                @if ($field->help)<p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $field->help }}</p>@endif
            </div>
            @continue
        @endif

        <section id="q{{ $fid }}" {!! $showAttr !!} class="rounded-2xl bg-white p-6 shadow-sm ring-1 {{ $err ? 'ring-red-300' : 'ring-slate-200' }}">
            <h2 class="text-base font-semibold leading-snug text-slate-900">{{ $field->label }} @if ($field->required)<span class="text-red-500" title="Wajib">*</span>@endif</h2>
            @if ($field->help)<p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $field->help }}</p>@endif

            <div class="mt-4">
            @switch($field->type)
                @case('short_text')
                    <input type="text" name="answers[{{ $fid }}]" value="{{ old('answers.'.$fid) }}" maxlength="255" class="input">
                    @break

                @case('long_text')
                    <textarea name="answers[{{ $fid }}]" rows="4" maxlength="5000" class="input">{{ old('answers.'.$fid) }}</textarea>
                    @break

                @case('single_choice')
                @case('multi_choice')
                    @php $multi = $field->type === 'multi_choice'; $old = (array) old('answers.'.$fid, []); @endphp
                    <div class="space-y-2">
                        @foreach ($field->options as $opt)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:bg-slate-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="{{ $multi ? 'checkbox' : 'radio' }}" name="answers[{{ $fid }}]{{ $multi ? '[]' : '' }}" value="{{ $opt['value'] }}" @checked(in_array($opt['value'], $old, true))
                                       @change="picks[{{ $fid }}] = [...$el.closest('section').querySelectorAll('input:checked')].map(i => i.value)" class="size-4 border-slate-300 text-brand-600 focus:ring-brand-500 {{ $multi ? 'rounded' : '' }}">
                                <span>{{ $opt['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                    @break

                @case('dropdown')
                    <select name="answers[{{ $fid }}]" class="input" @change="picks[{{ $fid }}] = $event.target.value">
                        <option value="">— Pilih —</option>
                        @foreach ($field->options as $opt)
                            <option value="{{ $opt['value'] }}" @selected(old('answers.'.$fid) === $opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @break

                @case('rating')
                    <div x-data="{ v: {{ (int) old('answers.'.$fid, 0) }}, h: 0 }" class="flex items-center gap-1">
                        <input type="hidden" name="answers[{{ $fid }}]" :value="v || ''">
                        @for ($n = 1; $n <= $field->maxRating(); $n++)
                            <button type="button" @click="v = (v === {{ $n }} ? 0 : {{ $n }})" @mouseenter="h = {{ $n }}" @mouseleave="h = 0"
                                    class="text-3xl leading-none transition" :class="(h || v) >= {{ $n }} ? 'text-amber-400' : 'text-slate-300'" aria-label="{{ $n }} bintang">★</button>
                        @endfor
                        <span class="ml-2 text-sm text-slate-500" x-text="v ? v + '/{{ $field->maxRating() }}' : ''"></span>
                    </div>
                    @break

                @case('image_choice')
                    <div x-data="{ sel: @js(old('answers.'.$fid, '')) }" x-effect="picks[{{ $fid }}] = sel">
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($field->options as $opt)
                                @php
                                    $items = collect($opt['gallery'] ?? [])->map(fn ($g) => ['src' => \App\Models\Form::img($g['src']), 'caption' => $g['caption'] ?? ''])->values();
                                    if ($items->isEmpty() && ! empty($opt['image'])) { $items = collect([['src' => \App\Models\Form::img($opt['image']), 'caption' => $opt['label']]]); }
                                    $lb = ['items' => $items->all(), 'index' => 0, 'title' => $opt['label']];
                                @endphp
                                <div class="flex flex-col overflow-hidden rounded-2xl border-2 bg-white transition"
                                     :class="sel === '{{ $opt['value'] }}' ? 'border-brand-500 ring-4 ring-brand-500/15' : 'border-slate-200 hover:border-slate-300'">
                                    @if (! empty($opt['image']))
                                        <button type="button" @click="$dispatch('open-lightbox', {{ \Illuminate\Support\Js::from(['items' => [['src' => \App\Models\Form::img($opt['image']), 'caption' => $opt['label']]], 'index' => 0, 'title' => $opt['label']]) }})"
                                                class="group relative block bg-slate-100" title="Klik untuk zoom">
                                            <img src="{{ \App\Models\Form::img($opt['image']) }}" alt="{{ $opt['label'] }}" loading="lazy" class="w-full object-contain">
                                            <span class="absolute bottom-2 right-2 rounded-md bg-slate-900/70 px-2 py-1 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100">🔍 Zoom</span>
                                        </button>
                                    @endif
                                    <div class="flex flex-1 flex-col gap-3 p-4">
                                        <div>
                                            <h3 class="font-semibold text-slate-900">{{ $opt['label'] }}</h3>
                                            @if (! empty($opt['description']))<p class="mt-0.5 text-sm text-slate-500">{{ $opt['description'] }}</p>@endif
                                        </div>
                                        <div class="mt-auto flex flex-wrap gap-2">
                                            @if ($items->count() > 1)
                                                <button type="button" @click="$dispatch('open-lightbox', {{ \Illuminate\Support\Js::from($lb) }})" class="btn-ghost text-xs">Lihat semua bahagian ({{ $items->count() }})</button>
                                            @endif
                                            <label class="btn flex-1 cursor-pointer text-sm"
                                                   :class="sel === '{{ $opt['value'] }}' ? 'bg-brand-600 text-white' : 'border border-brand-600 text-brand-700 hover:bg-brand-50'">
                                                <input type="radio" class="sr-only" name="answers[{{ $fid }}]" value="{{ $opt['value'] }}" x-model="sel">
                                                <span x-text="sel === '{{ $opt['value'] }}' ? '✓ Dipilih' : 'Pilih ini'"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($field->setting('allow_comment'))
                            <div class="mt-4" x-show="sel" x-collapse>
                                <label class="label">{{ $field->setting('comment_label') ?: 'Komen / sebab pilihan anda' }}</label>
                                <textarea name="comments[{{ $fid }}]" rows="3" maxlength="3000" class="input" placeholder="Taip komen anda di sini...">{{ old('comments.'.$fid) }}</textarea>
                            </div>
                        @endif
                    </div>
                    @break

                @case('part_feedback')
                    <div class="space-y-3">
                        @foreach ($field->options as $opt)
                            @php
                                $k = $opt['value'];
                                $st = old("answers.$fid.$k.status", '');
                            @endphp
                            <div x-data="{ st: @js($st) }" class="rounded-xl border p-3 transition sm:p-4"
                                 :class="st === 'ubah' ? 'border-amber-300 bg-amber-50/50' : (st === 'suka' ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200')">
                                <div class="flex gap-3 sm:gap-4">
                                    @if (! empty($opt['image']))
                                        <button type="button" @click="$dispatch('open-lightbox', {{ \Illuminate\Support\Js::from(['items' => [['src' => \App\Models\Form::img($opt['image']), 'caption' => $opt['label']]], 'index' => 0, 'title' => $opt['label']]) }})"
                                                class="group relative h-28 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 sm:h-36 sm:w-26" title="Klik untuk zoom">
                                            <img src="{{ \App\Models\Form::img($opt['image']) }}" alt="{{ $opt['label'] }}" loading="lazy" class="size-full object-cover object-top transition group-hover:scale-105">
                                        </button>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $opt['label'] }}</h3>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach (\App\Models\FormField::PART_STATUSES as $sv => $sl)
                                                @php $on = ['suka' => 'border-brand-600 bg-brand-600 text-white', 'boleh' => 'border-slate-600 bg-slate-600 text-white', 'ubah' => 'border-amber-500 bg-amber-500 text-white'][$sv]; @endphp
                                                <label class="cursor-pointer rounded-full border px-3 py-1 text-xs font-semibold transition"
                                                       :class="st === '{{ $sv }}' ? '{{ $on }}' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'">
                                                    <input type="radio" class="sr-only" name="answers[{{ $fid }}][{{ $k }}][status]" value="{{ $sv }}" x-model="st">
                                                    {{ $sl }}
                                                </label>
                                            @endforeach
                                            <button type="button" x-show="st" @click="st = ''; $root.querySelectorAll('input[type=radio]').forEach(r => r.checked = false)" class="px-1.5 text-xs text-slate-400 hover:text-slate-600">kosongkan</button>
                                        </div>
                                        <textarea name="answers[{{ $fid }}][{{ $k }}][comment]" rows="2" maxlength="2000"
                                                  class="input mt-2 text-sm" :class="st === 'ubah' && 'border-amber-400'"
                                                  :placeholder="st === 'ubah' ? 'Apa yang perlu ditambah baik? (wajib)' : 'Komen (pilihan)'">{{ old("answers.$fid.$k.comment") }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @break
            @endswitch
            </div>

            @if ($err)<p class="mt-3 text-sm font-medium text-red-600">{{ $err }}</p>@endif
        </section>
    @endforeach

    <div class="flex items-center justify-between gap-4 pt-2">
        <p class="text-xs text-slate-400">Maklum balas anda akan dihantar kepada pentadbir borang.</p>
        <button class="btn-primary px-6 py-3 text-base" :disabled="busy" x-text="busy ? 'Menghantar…' : '{{ $preview ? 'Hantar (ujian)' : 'Hantar maklum balas' }}'"></button>
    </div>
</form>

@if ($errors->any())
<script>document.addEventListener('DOMContentLoaded', () => { const e = document.querySelector('.ring-red-300, .border-red-400'); (e || document.getElementById('form-errors'))?.scrollIntoView({ block: 'center' }); });</script>
@endif
@endsection
