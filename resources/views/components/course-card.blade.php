@props(['course'])
<article {{ $attributes->merge(['class' => 'card card-hover group relative flex flex-col overflow-hidden p-3']) }}>
    <a href="{{ route('courses.show', $course) }}" class="block" tabindex="-1" aria-hidden="true">
        <x-course-cover :course="$course" />
    </a>
    <div class="flex flex-1 flex-col px-2 pb-2 pt-4">
        <div class="flex flex-wrap items-center gap-2">
            <span @class(['chip', 'chip-navy' => $course->type->value === 'recorded', 'chip-sky' => $course->type->value === 'live', 'chip-gray' => $course->type->value === 'offline'])>
                <x-dynamic-component :component="str_replace('heroicon-o-', 'heroicon-m-', $course->type->getIcon())" class="h-3.5 w-3.5" />
                {{ $course->type->getLabel() }}
            </span>
            @if ($course->level)<span class="chip chip-gray">{{ $course->level }}</span>@endif
        </div>
        <h3 class="mt-3 text-[1.05rem] font-semibold leading-snug">
            <a href="{{ route('courses.show', $course) }}" class="after:absolute after:inset-0 focus:outline-none group-hover:text-navy">{{ $course->title }}</a>
        </h3>
        <p class="mt-1.5 line-clamp-2 text-sm text-muted">{{ $course->instructor?->name }} · {{ $course->duration_hours }} ساعة</p>
        <div class="mt-auto flex items-end justify-between pt-4">
            <div class="font-mono">
                @if ($course->sale_price)
                    <span class="text-xs text-muted line-through">{{ number_format($course->price) }}</span>
                @endif
                <span class="text-lg font-semibold text-ink">{{ number_format($course->effectivePrice()) }}</span>
                <span class="text-xs text-muted">ج.م</span>
            </div>
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-navy-50 text-navy transition-colors group-hover:bg-navy group-hover:text-white" aria-hidden="true">
                <x-heroicon-m-arrow-left class="h-4 w-4" />
            </span>
        </div>
    </div>
</article>
