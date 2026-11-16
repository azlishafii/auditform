@extends('layouts.public')
@section('title', 'Terima kasih – '.$form->title)

@section('content')
@if ($test ?? false)
    <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <strong>Hantaran ujian direkodkan.</strong> Inilah halaman yang akan dilihat responden selepas menghantar.
        <div class="mt-2 flex flex-wrap gap-2">
            <a href="{{ route('admin.forms.results', $form) }}" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Lihat keputusan</a>
            <a href="{{ route('form.show', $form->slug) }}" class="rounded-lg border border-amber-400 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">Uji sekali lagi</a>
        </div>
    </div>
@endif

<div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200 sm:p-12">
    <div class="mx-auto mb-4 grid size-16 place-items-center rounded-full bg-brand-100 text-3xl text-brand-700">✓</div>
    <h1 class="font-display text-2xl font-bold text-slate-900">{{ $already ? 'Anda telah menghantar maklum balas' : 'Terima kasih!' }}</h1>
    <p class="mx-auto mt-3 max-w-md whitespace-pre-line text-slate-600">
        {{ $already ? 'Maklum balas anda untuk borang ini telah direkodkan sebelum ini.' : ($form->thank_you ?: 'Maklum balas anda telah berjaya direkodkan.') }}
    </p>
    <p class="mt-6 text-sm text-slate-400">{{ $form->title }}</p>
</div>
@endsection
