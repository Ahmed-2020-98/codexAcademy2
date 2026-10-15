@props(['value' => 0, 'segments' => 10, 'label' => null])
@php($on = (int) round(max(0, min(100, $value)) / 100 * $segments))
<div {{ $attributes->merge(['class' => 'cx-pixels']) }} style="--cx-segments: {{ $segments }}" role="progressbar" aria-valuenow="{{ (int) $value }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label ?? 'نسبة التقدم' }}">
    @for ($i = 1; $i <= $segments; $i++)
        <span @class(['is-on' => $i <= $on])></span>
    @endfor
</div>
