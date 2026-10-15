@props(['url' => null, 'title', 'duration' => null])
{{-- Bunny Stream embed, or a branded placeholder until the library is connected. --}}
<div {{ $attributes->merge(['class' => 'cx-player']) }}>
    @if ($url)
        <iframe src="{{ $url }}" title="{{ $title }}" loading="lazy" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
    @else
        <div class="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_30%_20%,rgb(51_184_246/0.25),transparent_55%)]">
            <div class="text-center text-white">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/20"><x-heroicon-s-play class="h-7 w-7 text-sky" /></span>
                <p class="mt-4 font-semibold">{{ $title }}</p>
                @if ($duration)<p class="mt-1 font-mono text-xs text-white/60">{{ $duration }}</p>@endif
                <p class="mt-3 text-xs text-white/50">يُعرض عبر Bunny Stream بعد ربط مكتبة الفيديو</p>
            </div>
        </div>
    @endif
    {{ $slot }}
</div>
