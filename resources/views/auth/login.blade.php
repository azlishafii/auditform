@extends('layouts.base')
@section('title', 'Log Masuk – '.config('app.name'))

@section('body')
<div class="grid min-h-screen place-items-center bg-gradient-to-br from-brand-50 via-white to-slate-100 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 grid size-12 place-items-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/25">
                <svg class="size-6" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4Zm2 3h8a1 1 0 1 1 0 2H6a1 1 0 0 1 0-2Zm0 4h8a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2Zm0 4h4a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2Z"/></svg>
            </div>
            <h1 class="text-xl font-bold text-slate-900">{{ config('app.name') }}</h1>
            <p class="text-sm text-slate-500">Log masuk untuk urus borang &amp; lihat maklum balas</p>
        </div>

        <form method="POST" action="{{ url('/login') }}" class="card space-y-4 p-6">
            @csrf
            <div>
                <label class="label" for="email">E-mel</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input">
            </div>
            <div>
                <label class="label" for="password">Kata laluan</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            @error('email')<p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>@enderror
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Ingat saya</label>
            <button class="btn-primary w-full">Log masuk</button>
        </form>
    </div>
</div>
@endsection
