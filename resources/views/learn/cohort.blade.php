<x-layouts.learn :title="$course->title">
    <a href="{{ route('learn.courses') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-navy"><x-heroicon-m-arrow-right class="h-4 w-4" /> كورساتي</a>
    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="chip chip-sky">{{ $course->type->getLabel() }}</span>
            <h1 class="mt-3 text-2xl font-bold sm:text-3xl">{{ $course->title }}</h1>
            <p class="mt-1 text-muted">{{ $group?->name }} · {{ $group?->instructor?->name }}</p>
        </div>
        @if ($group)
            <div class="flex flex-wrap gap-2">
                @if ($group->meeting_url)
                    <a href="{{ $group->meeting_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><x-heroicon-o-video-camera class="h-4 w-4" /> رابط الجلسات</a>
                @endif
                @if ($group->whatsapp_url)
                    <a href="{{ $group->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-heroicon-o-chat-bubble-left-right class="h-4 w-4" /> مجموعة واتساب</a>
                @endif
            </div>
        @endif
    </div>

    @if (! $group)
        <div class="card mt-8 p-8 text-center text-muted">سيتم إضافتك إلى مجموعة قريباً، وسيصلك الموعد على واتساب.</div>
    @else
        @if ($group->location)
            <p class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm shadow-[0_0_0_1px_var(--cx-line)]"><x-heroicon-o-map-pin class="h-5 w-5 text-sky" /> {{ $group->location }}</p>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <section class="card p-6 lg:col-span-2" aria-labelledby="sched-title">
                <h2 id="sched-title" class="font-semibold">جدول الجلسات والحضور</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-line text-xs text-muted"><th class="py-2 text-start font-medium">الجلسة</th><th class="py-2 text-start font-medium">الموعد</th><th class="py-2 text-start font-medium">حضوري</th></tr></thead>
                        <tbody>
                            @foreach ($group->sessions as $session)
                                @php($att = $attendance->get($session->id))
                                <tr class="border-b border-line last:border-0">
                                    <td class="py-3 font-medium">{{ $session->title }}</td>
                                    <td class="py-3 text-muted">{{ $session->starts_at->translatedFormat('D d M — h:i A') }}</td>
                                    <td class="py-3">
                                        @if ($session->status->value === 'cancelled')
                                            <span class="chip chip-gray">ملغاة</span>
                                        @elseif ($att)
                                            <span @class(['chip', 'chip-green' => $att->status->value === 'present', 'chip-amber' => $att->status->value === 'late', 'chip-red' => $att->status->value === 'absent', 'chip-gray' => $att->status->value === 'excused'])>{{ $att->status->getLabel() }}</span>
                                        @elseif ($session->starts_at->isFuture())
                                            <span class="chip chip-navy">قادمة</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card p-6" aria-labelledby="grades-title">
                <h2 id="grades-title" class="font-semibold">درجاتي</h2>
                <ul class="mt-4 grid gap-3">
                    @forelse ($grades as $grade)
                        @php($pct = round($grade->score / max(1, $grade->max_score) * 100))
                        <li class="rounded-xl bg-surface p-4">
                            <div class="flex justify-between text-sm"><span>{{ $grade->title }}</span><span class="font-mono font-semibold">{{ (float) $grade->score }}/{{ (float) $grade->max_score }}</span></div>
                            <x-pixel-progress :value="$pct" class="mt-2" />
                        </li>
                    @empty
                        <li class="text-sm text-muted">لم تُسجَّل درجات بعد.</li>
                    @endforelse
                </ul>
                <a href="{{ route('learn.tasks') }}" class="btn btn-ghost btn-sm mt-6 w-full">مهام هذه المجموعة</a>
            </section>
        </div>
    @endif
</x-layouts.learn>
