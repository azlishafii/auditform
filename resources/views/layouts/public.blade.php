@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-gradient-to-b from-brand-50/70 via-slate-50 to-slate-50">
    <div class="h-1.5 bg-gradient-to-r from-brand-600 via-brand-400 to-amber-400"></div>
    <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
        @yield('content')
        <p class="mt-10 text-center text-xs text-slate-400">{{ config('app.name') }}</p>
    </main>
</div>
@endsection
