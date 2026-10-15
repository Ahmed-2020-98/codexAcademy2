<x-layouts.learn title="كورساتي">
    <h1 class="text-2xl font-bold sm:text-3xl">كورساتي</h1>
    <p class="mt-2 text-muted">كل الكورسات التي اشتركت بها — المسجلة والتفاعلية.</p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($enrollments as $enrollment)
            <a href="{{ route('learn.course', $enrollment->course) }}" class="card card-hover flex flex-col p-3">
                <x-course-cover :course="$enrollment->course" />
                <div class="flex flex-1 flex-col px-2 pb-2 pt-4">
                    <div class="flex items-center gap-2">
                        <span class="chip chip-navy">{{ $enrollment->course->type->getLabel() }}</span>
                        @if ($enrollment->status === 'completed')<span class="chip chip-green">مكتمل</span>@endif
                    </div>
                    <h2 class="mt-3 font-semibold leading-snug">{{ $enrollment->course->title }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ $enrollment->group?->name ?? $enrollment->course->instructor?->name }}</p>
                    <div class="mt-auto pt-5">
                        <div class="mb-2 flex justify-between text-xs"><span class="text-muted">التقدم</span><span class="font-mono font-semibold">{{ $enrollment->progress }}%</span></div>
                        <x-pixel-progress :value="$enrollment->progress" :segments="16" />
                    </div>
                </div>
            </a>
        @empty
            <div class="card col-span-full grid place-items-center gap-3 px-6 py-16 text-center">
                <x-heroicon-o-book-open class="h-10 w-10 text-sky" />
                <p class="font-semibold">لم تشترك في أي كورس بعد</p>
                <a href="{{ route('courses.index') }}" class="btn btn-primary btn-sm">تصفّح الكورسات</a>
            </div>
        @endforelse
    </div>
</x-layouts.learn>
