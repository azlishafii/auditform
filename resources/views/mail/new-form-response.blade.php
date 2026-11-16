<x-mail::message>
# Maklum balas baharu diterima

Borang **{{ $form->title }}** telah menerima maklum balas baharu.

<x-mail::table>
| | |
|:--|:--|
| **Responden** | {{ $response->respondent_name ?: 'Tanpa nama' }} |
@if ($response->respondent_unit)
| **Jawatan / Unit** | {{ $response->respondent_unit }} |
@endif
| **Masa** | {{ $response->created_at->timezone(config('app.timezone'))->format('d/m/Y, g:i A') }} |
@foreach ($choices as $choice)
| **{{ $choice['question'] }}** | {{ $choice['answer'] }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$url">
Lihat maklum balas penuh
</x-mail::button>

Jumlah maklum balas setakat ini: **{{ $form->responses()->where('is_test', false)->count() }}**

{{ config('app.name') }}
</x-mail::message>
