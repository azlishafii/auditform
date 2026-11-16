@extends('layouts.public')
@section('title', $form->title)

@section('content')
<div class="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200 sm:p-12">
    <div class="mx-auto mb-4 grid size-16 place-items-center rounded-full bg-amber-100 text-3xl">🔒</div>
    <h1 class="font-display text-2xl font-bold text-slate-900">Borang ini tidak lagi menerima maklum balas</h1>
    <p class="mx-auto mt-3 max-w-md text-slate-600">{{ $form->title }}</p>
    <p class="mt-2 text-sm text-slate-400">Sila hubungi pentadbir borang jika anda memerlukan bantuan.</p>
</div>
@endsection
