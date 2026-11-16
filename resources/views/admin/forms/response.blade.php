@extends('layouts.admin')
@section('title', 'Maklum balas #'.$response->id.' – '.$form->title)

@section('content')
@include('admin.forms._tabs')

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.forms.responses', $form) }}" class="text-sm text-slate-500 hover:text-slate-800">← Senarai maklum balas</a>
        <h2 class="text-lg font-semibold text-slate-900">{{ $response->displayName() }}@if ($response->respondent_unit) <span class="font-normal text-slate-500">· {{ $response->respondent_unit }}</span>@endif</h2>
        <p class="text-sm text-slate-500">{{ $response->created_at->format('d/m/Y g:i A') }}</p>
    </div>
    <form method="POST" action="{{ route('admin.forms.response.destroy', [$form, $response]) }}" onsubmit="return confirm('Padam maklum balas ini?')">@csrf @method('DELETE')<button class="btn-danger">Padam</button></form>
</div>

<div class="space-y-4">
    @foreach ($form->fields as $field)
        @continue(! $field->isAnswerable())
        @php $a = $answers->get($field->id); @endphp
        <section class="card p-5">
            <h3 class="text-sm font-semibold text-slate-900">{{ $field->label }}</h3>
            <div class="mt-2 text-sm text-slate-700">
                @if (! $a)
                    <span class="text-slate-400">Tidak dijawab</span>
                @elseif ($field->type === 'part_feedback')
                    <div class="divide-y divide-slate-100">
                        @foreach ($field->options as $opt)
                            @php $p = $a->value[$opt['value']] ?? null; @endphp
                            @continue(! $p)
                            <div class="flex gap-3 py-2.5">
                                @if (! empty($opt['image']))<img src="{{ \App\Models\Form::img($opt['image']) }}" class="h-14 w-10 shrink-0 rounded border border-slate-200 object-cover object-top">@endif
                                <div>
                                    <p class="font-medium text-slate-800">{{ $opt['label'] }}
                                        @if ($p['status'] ?? null)<span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ ['suka' => 'bg-brand-100 text-brand-700', 'boleh' => 'bg-slate-100 text-slate-600', 'ubah' => 'bg-amber-100 text-amber-800'][$p['status']] }}">{{ \App\Models\FormField::PART_STATUSES[$p['status']] }}</span>@endif
                                    </p>
                                    @if ($p['comment'] ?? null)<p class="mt-0.5 whitespace-pre-line text-slate-600">{{ $p['comment'] }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="whitespace-pre-line">{{ \App\Http\Controllers\Admin\ResultController::plain($field, $a->value) }}</p>
                    @if ($a->comment)<p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-slate-600"><span class="mb-0.5 block text-xs font-semibold uppercase text-slate-400">Komen</span><span class="whitespace-pre-line">{{ $a->comment }}</span></p>@endif
                @endif
            </div>
        </section>
    @endforeach
</div>
@endsection
