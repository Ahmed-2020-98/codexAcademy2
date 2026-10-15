<x-layouts.site :title="$course->title" :description="$course->subtitle">
    @php($interactive = $course->type->isInteractive())

    <section class="cx-grid-bg border-b border-line">
        <div class="cx-container grid gap-10 py-10 lg:grid-cols-[1fr_24rem] lg:py-14">
            <div>
                <nav aria-label="مسار التصفح" class="text-sm text-muted">
                    <ol class="flex flex-wrap items-center gap-2">
                        <li><a href="{{ route('courses.index') }}" class="hover:text-navy">الكورسات</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('courses.index', ['department' => $course->department->slug]) }}" class="hover:text-navy">{{ $course->department->name }}</a></li>
                    </ol>
                </nav>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span @class(['chip', 'chip-navy' => ! $interactive, 'chip-sky' => $course->type->value === 'live', 'chip-gray' => $course->type->value === 'offline'])>
                        <x-dynamic-component :component="str_replace('heroicon-o-', 'heroicon-m-', $course->type->getIcon())" class="h-3.5 w-3.5" />
                        كورس {{ $course->type->getLabel() }}
                    </span>
                    @if ($course->level)<span class="chip chip-gray">المستوى: {{ $course->level }}</span>@endif
                    @if ($reviewsCount)<span class="chip chip-amber">★ {{ $rating }} ({{ $reviewsCount }} تقييم)</span>@endif
                </div>

                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-[2.6rem]">{{ $course->title }}</h1>
                <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">{{ $course->subtitle }}</p>

                <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4 text-sm">
                    <div class="flex items-center gap-2"><x-heroicon-o-user class="h-5 w-5 text-sky" /><dt class="sr-only">المحاضر</dt><dd><a href="{{ $course->instructor?->instructorProfile ? route('instructors.show', $course->instructor->instructorProfile) : '#' }}" class="font-semibold hover:text-navy">{{ $course->instructor?->name }}</a></dd></div>
                    <div class="flex items-center gap-2"><x-heroicon-o-clock class="h-5 w-5 text-sky" /><dt class="sr-only">المدة</dt><dd><span class="font-mono">{{ $course->duration_hours }}</span> ساعة</dd></div>
                    @if (! $interactive)
                        <div class="flex items-center gap-2"><x-heroicon-o-play-circle class="h-5 w-5 text-sky" /><dt class="sr-only">الدروس</dt><dd><span class="font-mono">{{ $course->sections->sum(fn ($s) => $s->lessons->count()) }}</span> درس</dd></div>
                    @endif
                    @if ($course->location)
                        <div class="flex items-center gap-2"><x-heroicon-o-map-pin class="h-5 w-5 text-sky" /><dt class="sr-only">المكان</dt><dd>{{ $course->location }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Purchase / enquiry card --}}
            <aside class="lg:row-span-2" aria-label="الاشتراك">
                <div class="card overflow-hidden lg:sticky lg:top-24">
                    @if ($previewUrl)
                        <div class="cx-player rounded-none"><iframe src="{{ $previewUrl }}" title="معاينة: {{ $preview->title }}" allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
                    @else
                        <x-course-cover :course="$course" class="rounded-none" />
                    @endif
                    <div class="p-6">
                        <div class="flex items-baseline gap-3 font-mono">
                            <span class="text-3xl font-semibold text-ink">{{ number_format($course->effectivePrice()) }}</span>
                            <span class="text-sm text-muted">ج.م</span>
                            @if ($course->sale_price)
                                <span class="text-sm text-muted line-through">{{ number_format($course->price) }}</span>
                                <span class="chip chip-green ms-auto">خصم {{ round((1 - $course->sale_price / $course->price) * 100) }}%</span>
                            @endif
                        </div>

                        @if ($enrolled)
                            <a href="{{ route('learn.course', $course) }}" class="btn btn-primary mt-6 w-full">متابعة التعلم <x-heroicon-m-arrow-left class="h-4 w-4" /></a>
                        @elseif (! $interactive)
                            <a href="{{ route('learn.checkout', $course) }}" class="btn btn-primary mt-6 w-full">اشترِ الآن وابدأ فوراً</a>
                            <p class="mt-3 text-center text-xs text-muted">لديك كود إحالة؟ أدخله في صفحة الدفع للحصول على خصم.</p>
                        @else
                            <a href="{{ route('register', ['course' => $course->slug]) }}" class="btn btn-primary mt-6 w-full">احجز مقعدك + اختبار المستوى</a>
                            <p class="mt-3 text-center text-xs text-muted">سيتواصل معك فريقنا خلال 24 ساعة لتأكيد المجموعة المناسبة.</p>
                        @endif

                        <ul class="mt-6 grid gap-2.5 border-t border-line pt-5 text-sm">
                            @if ($interactive)
                                <li class="cx-check">جلسات {{ $course->type->value === 'live' ? 'عبر Zoom / Google Meet' : 'حضورية في القاعة' }} + مجموعة واتساب</li>
                                <li class="cx-check">تسجيل حضور ودرجات ومتابعة من المحاضر</li>
                            @else
                                <li class="cx-check">بث آمن بعلامة مائية باسمك</li>
                                <li class="cx-check">اختبار نهائي وشهادة إتمام تلقائية</li>
                            @endif
                            <li class="cx-check">لوحة مهام أسبوعية شخصية</li>
                            <li class="cx-check">تذكيرات تلقائية على واتساب</li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <div class="cx-container grid gap-10 py-12 lg:grid-cols-[1fr_24rem]">
        <div class="grid gap-12">
            <section aria-labelledby="about-title">
                <h2 id="about-title" class="text-2xl font-bold">عن الكورس</h2>
                <p class="mt-4 leading-8 text-graphite">{{ $course->description }}</p>
                @if ($course->outcomes)
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($course->outcomes as $outcome)
                            <li class="cx-check card p-4">{{ $outcome }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if (! $interactive && $course->sections->isNotEmpty())
                <section aria-labelledby="curr-title">
                    <h2 id="curr-title" class="text-2xl font-bold">محتوى الكورس</h2>
                    <div class="mt-5 grid gap-3" x-data="{ open: 0 }">
                        @foreach ($course->sections as $i => $section)
                            <div class="card overflow-hidden">
                                <h3>
                                    <button type="button" class="flex w-full items-center gap-4 px-5 py-4 text-start" x-on:click="open = open === {{ $i }} ? null : {{ $i }}" :aria-expanded="open === {{ $i }}" aria-controls="sec-{{ $i }}">
                                        <span class="cx-step-num h-8! w-8! text-xs shadow-none!">{{ $i + 1 }}</span>
                                        <span class="flex-1 font-semibold">{{ $section->title }}</span>
                                        <span class="text-sm text-muted">{{ $section->lessons->count() }} دروس · {{ $section->lessons->sum('duration_minutes') }} د</span>
                                        <x-heroicon-m-chevron-down class="h-5 w-5 text-muted transition-transform" ::class="open === {{ $i }} && 'rotate-180'" />
                                    </button>
                                </h3>
                                <ul id="sec-{{ $i }}" x-show="open === {{ $i }}" x-collapse class="border-t border-line">
                                    @foreach ($section->lessons as $lesson)
                                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                                            <x-heroicon-o-play-circle class="h-5 w-5 shrink-0 text-sky" />
                                            <span class="flex-1">{{ $lesson->title }}</span>
                                            @if ($lesson->is_preview)<span class="chip chip-green">معاينة مجانية</span>@endif
                                            <span class="font-mono text-xs text-muted">{{ $lesson->duration_minutes }}:00</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($interactive)
                <section aria-labelledby="groups-title">
                    <h2 id="groups-title" class="text-2xl font-bold">المجموعات المتاحة</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @forelse ($course->groups as $group)
                            <article class="card p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-semibold">{{ $group->name }}</h3>
                                    <span @class(['chip', 'chip-green' => $group->students_count < $group->capacity, 'chip-red' => $group->students_count >= $group->capacity])>{{ max(0, $group->capacity - $group->students_count) }} مقعد</span>
                                </div>
                                <p class="mt-2 text-sm text-muted">يبدأ {{ $group->starts_on?->translatedFormat('d F Y') }}</p>
                                <ul class="mt-4 grid gap-1.5 text-sm">
                                    @foreach ($group->sessions as $session)
                                        <li class="flex items-center gap-2"><x-heroicon-m-calendar class="h-4 w-4 text-sky" /> {{ $session->starts_at->translatedFormat('l d M — h:i A') }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        @empty
                            <p class="text-muted">سيتم فتح مجموعة جديدة قريباً — سجّل اهتمامك ليصلك الموعد.</p>
                        @endforelse
                    </div>
                </section>
            @endif

            @if ($course->reviews->isNotEmpty())
                <section aria-labelledby="reviews-title">
                    <h2 id="reviews-title" class="text-2xl font-bold">تقييمات الطلاب</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($course->reviews as $review)
                            <figure class="card p-5">
                                <div class="font-mono text-sm text-sky" aria-label="{{ $review->rating }} من 5">{{ str_repeat('■', $review->rating) }}<span class="text-line">{{ str_repeat('■', 5 - $review->rating) }}</span></div>
                                <blockquote class="mt-3 leading-7">{{ $review->comment }}</blockquote>
                                <figcaption class="mt-3 text-sm font-semibold">{{ $review->user->name }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="cx-container" aria-labelledby="related-title">
            <h2 id="related-title" class="text-2xl font-bold">كورسات من نفس القسم</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-course-card :course="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
