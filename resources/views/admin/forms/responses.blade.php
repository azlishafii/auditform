@extends('layouts.admin')
@section('title', 'Maklum balas – '.$form->title)

@section('content')
@include('admin.forms._tabs')

<div class="card overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Tarikh</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($responses as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-400">{{ $r->id }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $r->displayName() }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->respondent_unit ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $r->created_at->format('d/m/Y g:i A') }}</td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('admin.forms.response', [$form, $r]) }}" class="font-medium text-brand-700 hover:underline">Lihat</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400">Belum ada maklum balas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $responses->links() }}</div>
@endsection
