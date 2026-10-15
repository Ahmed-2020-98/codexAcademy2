<x-layouts.learn title="لوحة الطالب">
    @php
        $done = $weekTasks->whereNotNull('completed_at')->count();
        $weekPct = $weekTasks->count() ? round($done / $weekTasks->count() * 100) : 0;
        $hour = (int) now()->format('G');
    @endphp

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-muted">{{ now()->translatedFormat('l، d F Y') }}</p>
            <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $hour < 12 ? 'صباح الخير' : 'مساء الخير' }}، {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1>
        </div>
        <a href="{{ route('courses.index') }}" class="btn btn-ghost btn-sm"><x-heroicon-o-plus class="h-4 w-4" /> كورس جديد</a>
    </header>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        {{-- Continue learning --}}
        <section class="card relative overflow-hidden p-6 lg:col-span-2" aria-labelledby="continue-title">
            @if ($continue)
                <div class="grid gap-6 sm:grid-cols-[13rem_1fr]">
                    <x-course-cover :course="$continue->course" />
                    <div class="flex flex-col">
                        <p class="eyebrow">تابع من حيث توقفت</p>
                        <h2 id="continue-title" class="mt-3 text-xl font-semibold">{{ $continue->course->title }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $continue->course->instructor?->name }}</p>
                        <div class="mt-auto pt-5">
                            <div class="mb-2 flex justify-between text-sm"><span class="text-muted">التقدم</span><span class="font-mono font-semibold">{{ $continue->progress }}%</span></div>
                            <x-pixel-progress :value="$continue->progress" :segments="20" />
                            <a href="{{ route('learn.course', $continue->course) }}" class="btn btn-primary btn-sm mt-5"><x-heroicon-s-play class="h-4 w-4" /> استكمل الدرس التالي</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-start gap-3 py-4">
                    <p class="eyebrow">ابدأ رحلتك</p>
                    <h2 id="continue-title" class="text-xl font-semibold">لا توجد كورسات مسجلة قيد التقدم</h2>
                    <p class="text-muted">اختر كورساً مسجلاً وابدأ فوراً، أو احجز مقعدك في مجموعة لايف.</p>
                    <a href="{{ route('courses.index') }}" class="btn btn-primary btn-sm mt-2">تصفّح الكورسات</a>
                </div>
            @endif
        </section>

        {{-- Weekly tasks --}}
        <section class="card flex flex-col p-6" aria-labelledby="week-title">
            <div class="flex items-center justify-between">
                <h2 id="week-title" class="font-semibold">مهام هذا الأسبوع</h2>
                <span class="font-mono text-sm text-navy">{{ $done }}/{{ $weekTasks->count() }}</span>
            </div>
            <x-pixel-progress :value="$weekPct" class="mt-4" label="إنجاز مهام الأسبوع" />
            <ul class="mt-5 grid gap-2 text-sm">
                @forelse ($weekTasks->take(4) as $task)
                    <li class="flex items-center gap-2.5">
                        @if ($task->completed_at)
                            <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-navy" />
                            <span class="truncate text-muted line-through decoration-sky decoration-2">{{ $task->title }}</span>
                        @else
                            <span class="h-4 w-4 shrink-0 rounded-[4px] border-2 border-slate-300 ms-0.5 me-0.5"></span>
                            <span class="truncate">{{ $task->title }}</span>
                        @endif
                    </li>
                @empty
                    <li class="text-muted">لا توجد مهام هذا الأسبوع.</li>
                @endforelse
            </ul>
            <a href="{{ route('learn.tasks') }}" class="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-navy hover:underline">فتح «مهامي» <x-heroicon-m-arrow-left class="h-4 w-4" /></a>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Upcoming sessions --}}
        <section class="card p-6 lg:col-span-2" aria-labelledby="sess-title">
            <h2 id="sess-title" class="font-semibold">الجلسات القادمة</h2>
            <ul class="mt-4 divide-y divide-line">
                @forelse ($sessions as $session)
                    @php($live = $session->starts_at->lte(now()->addMinutes(15)) && $session->starts_at->gte(now()->subHours(2)))
                    <li class="flex flex-wrap items-center gap-4 py-3.5">
                        <div class="grid w-14 shrink-0 place-items-center rounded-lg bg-navy-50 py-1.5 text-navy">
                            <span class="font-mono text-lg font-semibold leading-none">{{ $session->starts_at->format('d') }}</span>
                            <span class="text-[0.65rem]">{{ $session->starts_at->translatedFormat('M') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium">{{ $session->title }} — {{ $session->group->course->title }}</div>
                            <div class="text-sm text-muted">{{ $session->starts_at->translatedFormat('l h:i A') }} · {{ $session->group->mode->getLabel() }}</div>
                        </div>
                        @if ($session->group->mode->value === 'live' && $session->joinUrl())
                            <a href="{{ $session->joinUrl() }}" target="_blank" rel="noopener" @class(['btn btn-sm', 'btn-primary' => $live, 'btn-ghost' => ! $live])>
                                <x-heroicon-o-video-camera class="h-4 w-4" /> {{ $live ? 'انضم الآن' : 'رابط الجلسة' }}
                            </a>
                        @else
                            <span class="chip chip-gray"><x-heroicon-m-map-pin class="h-3.5 w-3.5" /> {{ \Illuminate\Support\Str::limit($session->group->location, 26) }}</span>
                        @endif
                    </li>
                @empty
                    <li class="py-6 text-sm text-muted">لا توجد جلسات مجدولة.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-6" aria-labelledby="sum-title">
            <h2 id="sum-title" class="font-semibold">ملخصك</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-surface p-4"><dt class="text-xs text-muted">كورساتي</dt><dd class="mt-1 font-mono text-2xl font-semibold">{{ $enrollments->count() }}</dd></div>
                <div class="rounded-xl bg-surface p-4"><dt class="text-xs text-muted">الشهادات</dt><dd class="mt-1 font-mono text-2xl font-semibold">{{ $certificates }}</dd></div>
                <div class="col-span-2 rounded-xl bg-navy p-4 text-white"><dt class="text-xs text-white/70">متوسط التقدم</dt><dd class="mt-1 font-mono text-2xl font-semibold">{{ round($enrollments->avg('progress') ?? 0) }}%</dd></div>
            </dl>
        </section>
    </div>
</x-layouts.learn>
