@props(['course', 'size' => 'md'])
@php
    // Deterministic pixel composition per course, so every cover is unique but stable.
    $seed = crc32($course->slug);
    $palette = ['recorded' => '#0A5A9E', 'live' => '#0B4F86', 'offline' => '#1E2430'];
    $bg = $course->department?->color && $course->type->value !== 'offline' ? $course->department->color : ($palette[$course->type->value] ?? '#0A5A9E');
    // Light department colours would swallow the sky pixels and the white label, so fall back to navy.
    [$r, $g, $b] = sscanf(ltrim($bg, '#'), '%02x%02x%02x') + [0, 0, 0];
    if ((0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.55) {
        $bg = '#0A5A9E';
    }
    $cells = [];
    for ($i = 0; $i < 84; $i++) {
        $n = ($seed >> ($i % 24)) ^ ($i * 2654435761);
        $r = abs($n) % 17;
        $cells[] = match (true) { $r === 0 => 'on-1', $r === 1 => 'on-2', $r < 4 => 'on-3', default => '' };
    }
@endphp
<div {{ $attributes->merge(['class' => 'cx-cover']) }} style="--cover-bg: {{ $bg }}" aria-hidden="true">
    <div class="cx-cover__grid">
        @foreach ($cells as $cell)<i class="{{ $cell }}"></i>@endforeach
    </div>
    <span class="cx-cover__label">{{ $course->department?->name_en ?? 'Codex' }} · {{ $course->type->value }}</span>
</div>
