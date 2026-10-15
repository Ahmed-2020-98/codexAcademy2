<div class="grid gap-8 xl:grid-cols-[1fr_21rem]">
    <div class="min-w-0">
        <a href="{{ route('learn.courses') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-navy"><x-heroicon-m-arrow-right class="h-4 w-4" /> {{ $course->title }}</a>

        {{-- Secure player: Bunny Stream iframe + dynamic watermark that moves and self-heals. --}}
        <div class="mt-4"
             x-data="watermark(@js($watermark))"
             x-init="start()">
            <x-video-frame :url="$embedUrl" :title="$lesson->title" :duration="$lesson->duration_minutes.':00'" x-ref="player">
                <div class="cx-watermark" x-ref="mark" :style="`top:${y}%;left:${x}%`" aria-hidden="true"><span x-text="text"></span></div>
            </x-video-frame>
        </div>

        <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold sm:text-2xl">{{ $lesson->title }}</h1>
                <p class="mt-1 text-sm text-muted">{{ $lesson->section?->title }} · <span class="font-mono">{{ $lesson->duration_minutes }}</span> دقيقة</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (in_array($lesson->id, $done))
                    <span class="chip chip-green h-11 px-4 text-sm"><x-heroicon-m-check-circle class="h-5 w-5" /> تمت المشاهدة</span>
                    @if ($next)
                        <a href="{{ route('learn.lesson', [$course, $next]) }}" class="btn btn-primary">الدرس التالي <x-heroicon-m-arrow-left class="h-4 w-4" /></a>
                    @endif
                @elseif ($enrollment)
                    <button type="button" wire:click="complete" wire:loading.attr="disabled" class="btn btn-primary">
                        <x-heroicon-m-check class="h-4 w-4" />
                        <span wire:loading.remove wire:target="complete">{{ $next ? 'أنهيت الدرس — التالي' : 'أنهيت الدرس' }}</span>
                        <span wire:loading wire:target="complete">جارٍ الحفظ…</span>
                    </button>
                @endif
            </div>
        </div>

        @if ($lesson->content)
            <div class="card mt-6 p-6">
                <h2 class="font-semibold">ملخص الدرس</h2>
                <p class="mt-2 leading-8 text-graphite">{{ $lesson->content }}</p>
            </div>
        @endif

        @if ($enrollment && $enrollment->progress >= 100)
            <div class="card mt-6 flex flex-wrap items-center gap-5 bg-navy! p-6 text-white">
                <x-heroicon-o-trophy class="h-10 w-10 text-sky" />
                <div class="flex-1">
                    @if ($certificate)
                        <h2 class="font-semibold">مبروك! شهادتك جاهزة</h2>
                        <p class="text-sm text-white/75">رقم التحقق: <span class="font-mono" dir="ltr">{{ $certificate->serial }}</span></p>
                    @else
                        <h2 class="font-semibold">أنهيت كل الدروس 👏</h2>
                        <p class="text-sm text-white/75">اجتز الاختبار النهائي لتصدر شهادتك تلقائياً.</p>
                    @endif
                </div>
                @if ($certificate)
                    <a href="{{ route('certificates.show', $certificate) }}" target="_blank" class="btn btn-light btn-sm">عرض الشهادة</a>
                @elseif ($quiz)
                    <a href="{{ route('learn.quiz', $course) }}" class="btn btn-light btn-sm">ابدأ الاختبار النهائي</a>
                @endif
            </div>
        @endif
    </div>

    <aside class="card h-fit overflow-hidden xl:sticky xl:top-8" aria-label="محتوى الكورس">
        <div class="border-b border-line p-5">
            <div class="mb-2 flex justify-between text-sm"><span class="font-semibold">تقدمك</span><span class="font-mono">{{ $enrollment?->progress ?? 0 }}%</span></div>
            <x-pixel-progress :value="$enrollment?->progress ?? 0" :segments="16" />
        </div>
        <nav class="max-h-[60vh] overflow-y-auto p-2">
            @foreach ($sections as $section)
                <p class="px-3 pb-1 pt-3 text-xs font-semibold text-muted">{{ $section->title }}</p>
                <ul>
                    @foreach ($section->lessons as $item)
                        @php($isDone = in_array($item->id, $done))
                        <li>
                            <a href="{{ route('learn.lesson', [$course, $item]) }}" @if ($item->is($lesson)) aria-current="true" @endif
                               @class(['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors', 'bg-navy-50 font-semibold text-navy' => $item->is($lesson), 'hover:bg-surface' => ! $item->is($lesson)])>
                                @if ($isDone)
                                    <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-navy" aria-label="مكتمل" />
                                @else
                                    <x-heroicon-o-play-circle class="h-5 w-5 shrink-0 text-slate-400" />
                                @endif
                                <span class="flex-1 leading-snug">{{ $item->title }}</span>
                                <span class="font-mono text-xs text-muted">{{ $item->duration_minutes }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
            @if ($quiz)
                <a href="{{ route('learn.quiz', $course) }}" class="mt-2 flex items-center gap-3 rounded-lg border-t border-line px-3 py-3 text-sm font-semibold hover:bg-surface">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-sky" />
                    <span class="flex-1">الاختبار النهائي</span>
                    @if ($passed)<span class="chip chip-green">ناجح</span>@endif
                </a>
            @endif
        </nav>
    </aside>
</div>
