@props(['animated' => false])
{{-- The Codex mark: two chevrons < > built from rounded squares. --}}
<svg {{ $attributes->merge(['class' => 'cx-mark'.($animated ? ' cx-mark--animated' : '')]) }} viewBox="0 0 634 569" role="img" aria-label="Codex">
    <g class="cx-mark__sky">
        <rect x="142" y="142" width="142" height="142" rx="12" style="--i:0"/>
        <rect x="0" y="285" width="142" height="142" rx="12" style="--i:1"/>
        <rect x="142" y="427" width="142" height="142" rx="12" style="--i:2"/>
    </g>
    <g class="cx-mark__navy">
        <rect x="350" y="0" width="142" height="142" rx="12" style="--i:3"/>
        <rect x="492" y="142" width="142" height="142" rx="12" style="--i:4"/>
        <rect x="350" y="285" width="142" height="142" rx="12" style="--i:5"/>
    </g>
</svg>
