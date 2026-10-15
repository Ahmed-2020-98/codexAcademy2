@props(['tagline' => true])
<span {{ $attributes->merge(['class' => 'cx-logo']) }} dir="ltr">
    <x-brand.mark class="cx-logo__mark" />
    <span class="cx-logo__text">
        <span class="cx-logo__word">Codex</span>
        @if ($tagline)
            <span class="cx-logo__tag">Academy</span>
        @endif
    </span>
</span>
