@extends('layouts.base')

@section('body')
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4">
        <a href="{{ route('admin.forms.index') }}" class="flex items-center gap-2.5 font-semibold text-slate-900">
            <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-white">
                <svg class="size-4.5" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4Zm2 3h8a1 1 0 1 1 0 2H6a1 1 0 0 1 0-2Zm0 4h8a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2Zm0 4h4a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2Z"/></svg>
            </span>
            {{ config('app.name') }}
        </a>
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.forms.index') }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100">Semua Borang</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="rounded-lg px-3 py-1.5 font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-800">Log keluar</button>
            </form>
        </nav>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-8">
    @if (session('status'))
        <div x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 5000)" x-transition
             class="mb-6 flex items-center justify-between rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
            <span>{{ session('status') }}</span>
            <button type="button" @click="s = false" class="text-brand-700/70 hover:text-brand-800">✕</button>
        </div>
    @endif
    @yield('content')
</main>
@endsection
