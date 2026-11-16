@extends('layouts.admin')
@section('title', 'Keputusan – '.$form->title)

@section('content')
@include('admin.forms._tabs')

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="card p-5"><p class="text-sm text-slate-500">Jumlah maklum balas</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $total }}</p></div>
    <div class="card p-5"><p class="text-sm text-slate-500">Maklum balas terakhir</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ $last ? $last->created_at->diffForHumans() : '—' }}</p></div>
    <div class="card p-5"><p class="text-sm text-slate-500">Bilangan soalan</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ count($summaries) }}</p></div>
</div>

@if ($testCount > 0)
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <span>Keputusan di bawah termasuk <strong>{{ $testCount }}</strong> maklum balas <strong>ujian</strong>. Padam data ujian sebelum membuka borang kepada responden.</span>
        <form method="POST" action="{{ route('admin.forms.tests.destroy', $form) }}" onsubmit="return confirm('Padam {{ $testCount }} maklum balas ujian? Maklum balas sebenar tidak terjejas.')">
            @csrf @method('DELETE')
            <button class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Padam data ujian</button>
        </form>
    </div>
@endif

@if ($total === 0)
    <div class="card grid place-items-center px-6 py-14 text-center">
        <div class="mb-3 text-4xl">📭</div>
        <h2 class="font-semibold text-slate-900">Belum ada maklum balas</h2>
        <p class="mt-1 text-sm text-slate-500">Kongsi pautan borang di atas kepada responden. Keputusan akan dipaparkan di sini.</p>
    </div>
@else
<div class="space-y-5">
    @foreach ($form->fields as $field)
        @continue(! $field->isAnswerable())
        @php $s = $summaries[$field->id]; $statusColor = ['suka' => 'bg-brand-500', 'boleh' => 'bg-slate-400', 'ubah' => 'bg-amber-500']; @endphp
        <section class="card p-6">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-semibold text-slate-900">{{ $field->label }}</h2>
                <span class="text-xs text-slate-500">{{ $s['answered'] }} / {{ $s['total'] }} menjawab</span>
            </div>

            @if ($s['kind'] === 'choice')
                <div class="space-y-4">
                    @foreach ($s['rows'] as $row)
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2 font-medium text-slate-800">
                                    @if ($field->type === 'image_choice' && ! empty($row['option']['image']))
                                        <button type="button" @click="$dispatch('open-lightbox', {{ \Illuminate\Support\Js::from(['items' => [['src' => \App\Models\Form::img($row['option']['image']), 'caption' => $row['option']['label']]], 'title' => $row['option']['label']]) }})"><img src="{{ \App\Models\Form::img($row['option']['image']) }}" class="h-12 w-9 rounded border border-slate-200 object-cover object-top"></button>
                                    @endif
                                    {{ $row['option']['label'] }}
                                    @if ($row['leader'] && $s['answered'] > 1)<span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700">Teratas</span>@endif
                                </span>
                                <span class="shrink-0 tabular-nums text-slate-600"><strong class="text-slate-900">{{ $row['count'] }}</strong> · {{ $row['pct'] }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $row['leader'] ? 'bg-brand-500' : 'bg-slate-400' }}" style="width: {{ $row['bar'] }}%"></div></div>
                            @if ($row['comments'])
                                <details class="mt-2 text-sm">
                                    <summary class="cursor-pointer text-xs font-medium text-brand-700">{{ count($row['comments']) }} komen</summary>
                                    <ul class="mt-2 space-y-2">
                                        @foreach ($row['comments'] as $c)
                                            <li class="rounded-lg bg-slate-50 px-3 py-2"><p class="whitespace-pre-line text-slate-700">{{ $c['text'] }}</p><p class="mt-0.5 text-xs text-slate-400">— {{ $c['who']->displayName() }}{{ $c['who']->respondent_unit ? ', '.$c['who']->respondent_unit : '' }}</p></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>

            @elseif ($s['kind'] === 'rating')
                <div class="flex flex-wrap items-center gap-6">
                    <div><p class="text-4xl font-bold text-slate-900">{{ $s['avg'] ?? '—' }}<span class="text-lg font-medium text-slate-400"> / {{ $s['max'] }}</span></p><p class="text-xs text-slate-500">purata</p></div>
                    <div class="min-w-48 flex-1 space-y-1">
                        @php $dm = max(1, max($s['dist'])); @endphp
                        @foreach (array_reverse($s['dist'], true) as $n => $c)
                            <div class="flex items-center gap-2 text-xs"><span class="w-6 text-right text-slate-500">{{ $n }}★</span><div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-amber-400" style="width: {{ $c / $dm * 100 }}%"></div></div><span class="w-6 tabular-nums text-slate-600">{{ $c }}</span></div>
                        @endforeach
                    </div>
                </div>

            @elseif ($s['kind'] === 'parts')
                <div class="mb-3 flex flex-wrap gap-3 text-xs text-slate-500">
                    @foreach (\App\Models\FormField::PART_STATUSES as $sv => $sl)<span class="flex items-center gap-1.5"><i class="size-2.5 rounded-full {{ $statusColor[$sv] }}"></i>{{ $sl }}</span>@endforeach
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($s['rows'] as $row)
                        <div class="py-4">
                            <div class="flex items-center gap-3">
                                @if (! empty($row['option']['image']))
                                    <button type="button" @click="$dispatch('open-lightbox', {{ \Illuminate\Support\Js::from(['items' => [['src' => \App\Models\Form::img($row['option']['image']), 'caption' => $row['option']['label']]], 'title' => $row['option']['label']]) }})"><img src="{{ \App\Models\Form::img($row['option']['image']) }}" class="h-14 w-10 rounded border border-slate-200 object-cover object-top"></button>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-800">{{ $row['option']['label'] }}</p>
                                    <div class="mt-1.5 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                                        @foreach ($row['counts'] as $sv => $c)
                                            @if ($c && $row['total'])<div class="{{ $statusColor[$sv] }}" style="width: {{ $c / $row['total'] * 100 }}%" title="{{ \App\Models\FormField::PART_STATUSES[$sv] }}: {{ $c }}"></div>@endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="shrink-0 text-right text-xs tabular-nums text-slate-600">
                                    @foreach ($row['counts'] as $sv => $c)<span class="ml-2"><i class="mr-1 inline-block size-2 rounded-full {{ $statusColor[$sv] }}"></i>{{ $c }}</span>@endforeach
                                </div>
                            </div>
                            @if ($row['comments'])
                                <ul class="mt-2.5 space-y-1.5 pl-0 sm:pl-[3.25rem]">
                                    @foreach ($row['comments'] as $c)
                                        <li class="rounded-lg px-3 py-2 text-sm {{ ($c['status'] ?? '') === 'ubah' ? 'bg-amber-50 text-amber-900' : 'bg-slate-50 text-slate-700' }}">
                                            @if ($c['status'] ?? null)<span class="mr-1 rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold ring-1 ring-black/5">{{ \App\Models\FormField::PART_STATUSES[$c['status']] }}</span>@endif
                                            <span class="whitespace-pre-line">{{ $c['text'] }}</span>
                                            <span class="block text-xs text-slate-400">— {{ $c['who']->displayName() }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>

            @else
                @if (! $s['items'])
                    <p class="text-sm text-slate-400">Tiada jawapan.</p>
                @else
                    <ul class="max-h-96 space-y-2 overflow-y-auto pr-1">
                        @foreach ($s['items'] as $it)
                            <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm"><p class="whitespace-pre-line text-slate-700">{{ $it['text'] }}</p><p class="mt-0.5 text-xs text-slate-400">— {{ $it['who']->displayName() }}{{ $it['who']->respondent_unit ? ', '.$it['who']->respondent_unit : '' }}</p></li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>
    @endforeach
</div>
@endif
@endsection
