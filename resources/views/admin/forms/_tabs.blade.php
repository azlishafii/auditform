@php $r = request()->route()->getName(); @endphp
<div class="mb-6">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('admin.forms.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Semua borang</a>
            <h1 class="text-2xl font-bold leading-tight text-slate-900">{{ $form->title }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['Dibuka' => 'bg-emerald-100 text-emerald-700', 'Draf' => 'bg-slate-100 text-slate-600'][$form->statusLabel()] ?? 'bg-amber-100 text-amber-700' }}">{{ $form->statusLabel() }}</span>
                <code class="max-w-full truncate rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ route('form.show', $form->slug) }}</code>
                <button type="button" onclick="copyText('{{ route('form.show', $form->slug) }}', this)" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">Salin pautan</button>
                <a href="{{ route('form.show', $form->slug) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">{{ $form->status === 'draft' ? 'Pratonton' : 'Buka' }} ↗</a>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($form->status === 'open')
                <form method="POST" action="{{ route('admin.forms.status', $form) }}">@csrf<input type="hidden" name="status" value="closed"><button class="btn-ghost">Tutup borang</button></form>
            @else
                <form method="POST" action="{{ route('admin.forms.status', $form) }}">@csrf<input type="hidden" name="status" value="open"><button class="btn-primary">Buka borang</button></form>
            @endif
            <a href="{{ route('admin.forms.edit', $form) }}" class="btn-ghost">Sunting</a>
            <a href="{{ route('admin.forms.export', $form) }}" class="btn-ghost">Eksport CSV</a>
        </div>
    </div>
    <nav class="flex gap-1 border-b border-slate-200 text-sm font-medium">
        <a href="{{ route('admin.forms.results', $form) }}" class="-mb-px border-b-2 px-4 py-2.5 {{ $r === 'admin.forms.results' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">Ringkasan</a>
        <a href="{{ route('admin.forms.responses', $form) }}" class="-mb-px border-b-2 px-4 py-2.5 {{ in_array($r, ['admin.forms.responses', 'admin.forms.response']) ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">Maklum balas individu</a>
    </nav>
</div>
