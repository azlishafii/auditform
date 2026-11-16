@extends('layouts.admin')
@section('title', 'Semua Borang – '.config('app.name'))

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Borang</h1>
        <p class="text-sm text-slate-500">Cipta borang pemilihan, kaji selidik atau maklum balas dan kongsi pautannya.</p>
    </div>
    <a href="{{ route('admin.forms.create') }}" class="btn-primary">+ Borang baharu</a>
</div>

@php $badge = ['Dibuka' => 'bg-emerald-100 text-emerald-700', 'Draf' => 'bg-slate-100 text-slate-600', 'Ditutup' => 'bg-amber-100 text-amber-700', 'Tamat tempoh' => 'bg-amber-100 text-amber-700']; @endphp

@forelse ($forms as $form)
    @if ($loop->first)<div class="grid gap-4">@endif
    <div class="card p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.forms.results', $form) }}" class="text-lg font-semibold text-slate-900 hover:text-brand-700">{{ $form->title }}</a>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge[$form->statusLabel()] ?? '' }}">{{ $form->statusLabel() }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $form->fields_count }} soalan · <strong class="text-slate-700">{{ $form->responses_count }}</strong> maklum balas · dicipta {{ $form->created_at->diffForHumans() }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <code class="max-w-full truncate rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ route('form.show', $form->slug) }}</code>
                    <button type="button" onclick="copyText('{{ route('form.show', $form->slug) }}', this)" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Salin pautan</button>
                    <a href="{{ route('form.show', $form->slug) }}" target="_blank" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ $form->status === 'draft' ? 'Pratonton' : 'Buka' }} ↗</a>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.forms.results', $form) }}" class="btn-ghost">Keputusan</a>
                <a href="{{ route('admin.forms.edit', $form) }}" class="btn-ghost">Sunting</a>
                <div x-data="{ o: false }" class="relative">
                    <button type="button" @click="o = !o" @click.outside="o = false" class="btn-ghost px-3" aria-label="Lagi">⋯</button>
                    <div x-show="o" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                        @if ($form->status !== 'open')
                            <form method="POST" action="{{ route('admin.forms.status', $form) }}">@csrf<input type="hidden" name="status" value="open"><button class="block w-full px-4 py-2 text-left hover:bg-slate-50">Buka borang</button></form>
                        @endif
                        @if ($form->status === 'open')
                            <form method="POST" action="{{ route('admin.forms.status', $form) }}">@csrf<input type="hidden" name="status" value="closed"><button class="block w-full px-4 py-2 text-left hover:bg-slate-50">Tutup borang</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.forms.duplicate', $form) }}">@csrf<button class="block w-full px-4 py-2 text-left hover:bg-slate-50">Duplikasi</button></form>
                        <a href="{{ route('admin.forms.export', $form) }}" class="block px-4 py-2 hover:bg-slate-50">Eksport CSV</a>
                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" onsubmit="return confirm('Padam borang ini beserta {{ $form->responses_count }} maklum balas? Tindakan ini tidak boleh dibatalkan.')">@csrf @method('DELETE')<button class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">Padam</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($loop->last)</div>@endif
@empty
    <div class="card grid place-items-center px-6 py-16 text-center">
        <div class="mb-3 text-4xl">📝</div>
        <h2 class="font-semibold text-slate-900">Belum ada borang</h2>
        <p class="mb-5 mt-1 text-sm text-slate-500">Mulakan dengan mencipta borang pertama anda.</p>
        <a href="{{ route('admin.forms.create') }}" class="btn-primary">+ Borang baharu</a>
    </div>
@endforelse
@endsection
