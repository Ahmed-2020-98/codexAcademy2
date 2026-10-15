<x-layouts.site>
    {{-- ============ HERO ============ --}}
    <section class="cx-grid-bg relative -mt-16 overflow-hidden pt-16 lg:-mt-20 lg:pt-20">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-surface"></div>
        <div class="cx-container relative grid items-center gap-12 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
            <div>
                <p class="eyebrow eyebrow-latin cx-reveal" style="--d:0" dir="ltr">Codex Academy · LMS</p>
                <h1 class="cx-reveal mt-6 text-[2.35rem] font-bold leading-[1.15] text-ink sm:text-5xl lg:text-[3.6rem]" style="--d:1">
                    تعلّم بخطة واضحة،
                    <span class="relative whitespace-nowrap text-navy">
                        وتقدّم بمتابعة
                        <svg class="absolute -bottom-2 start-0 h-3 w-full" viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 9 Q 50 1 100 6 T 198 4" fill="none" stroke="#33B8F6" stroke-width="4" stroke-linecap="round"/></svg>
                    </span>
                    حقيقية.
                </h1>
                <p class="cx-reveal mt-6 max-w-xl text-lg leading-8 text-muted" style="--d:2">
                    كورسات مسجلة ببث آمن، وجلسات لايف وحضورية في اللغات والبرمجة. لكل طالب لوحة مهام أسبوعية، ومتابعة شخصية من فريقنا خلال 24 ساعة من التسجيل، وشهادة إتمام قابلة للتحقق.
                </p>
                <div class="cx-reveal mt-9 flex flex-wrap gap-3" style="--d:3">
                    <a href="{{ route('register') }}" class="btn btn-primary">
                        احجز اختبار تحديد المستوى مجاناً
                        <x-heroicon-m-arrow-left class="h-4 w-4" />
                    </a>
                    <a href="{{ route('courses.index') }}" class="btn btn-ghost">تصفّح الكورسات</a>
                </div>

                <dl class="cx-reveal mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-line pt-8" style="--d:4">
                    <div><dt class="text-xs text-muted">طالب</dt><dd class="mt-1 font-mono text-2xl font-semibold text-ink">{{ number_format($stats['students']) }}+</dd></div>
                    <div><dt class="text-xs text-muted">كورس</dt><dd class="mt-1 font-mono text-2xl font-semibold text-ink">{{ $stats['courses'] }}</dd></div>
                    <div><dt class="text-xs text-muted">ساعة SLA للتواصل</dt><dd class="mt-1 font-mono text-2xl font-semibold text-navy">24h</dd></div>
                </dl>
            </div>

            {{-- The brand mark, surrounded by real platform UI pieces. --}}
            <div class="cx-hero-art" aria-hidden="true">
                <x-brand.mark animated />

                <div class="cx-float start-0 top-[6%] w-52">
                    <div class="flex items-center justify-between text-[0.7rem] text-muted"><span>مهامي — الأسبوع 3</span><span class="font-mono text-navy">70%</span></div>
                    <x-pixel-progress :value="70" class="mt-2" />
                </div>

                <div class="cx-float bottom-[8%] end-0 flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-green-100 text-green-700"><x-heroicon-s-check-badge class="h-5 w-5" /></span>
                    <div>
                        <div class="text-[0.7rem] text-muted">تم إصدار الشهادة</div>
                        <div class="font-mono text-xs font-semibold" dir="ltr">CDX-2026-8K2QX</div>
                    </div>
                </div>

                <div class="cx-float bottom-[30%] start-[-2%] flex items-center gap-2.5">
                    <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky opacity-60"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-sky"></span></span>
                    <div>
                        <div class="text-[0.7rem] text-muted">سيتواصل معك فريقنا خلال</div>
                        <div class="font-mono text-sm font-semibold text-navy" dir="ltr">23:41:08</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ DELIVERY MODELS ============ --}}
    <section class="cx-container py-16 lg:py-20" aria-labelledby="models-title">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="eyebrow">نماذج التعلم</p>
                <h2 id="models-title" class="mt-4 text-3xl font-bold sm:text-4xl">اختر الطريقة التي تناسبك</h2>
            </div>
            <p class="max-w-md text-muted">نموذج مدمج واحد: نفس المتابعة ونفس لوحة المهام، سواء تعلّمت من البيت أو في القاعة.</p>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ($types as $i => $type)
                <a href="{{ route('courses.index', ['type' => $type->value]) }}" @class([
                    'card card-hover group relative overflow-hidden p-7',
                    'bg-navy! text-white' => $i === 1,
                ])>
                    <div @class(['grid h-12 w-12 place-items-center rounded-xl', 'bg-white/15 text-white' => $i === 1, 'bg-navy-50 text-navy' => $i !== 1])>
                        <x-dynamic-component :component="$type->getIcon()" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-6 text-xl font-semibold">كورسات {{ $type->getLabel() }}</h3>
                    <p @class(['mt-3 text-[0.95rem] leading-7', 'text-white/80' => $i === 1, 'text-muted' => $i !== 1])>{{ $type->description() }}</p>
                    <div class="mt-6 flex items-center justify-between">
                        <span @class(['text-sm font-semibold', 'text-sky' => $i === 1, 'text-navy' => $i !== 1])><span class="font-mono">{{ $typeCounts[$type->value] ?? 0 }}</span> كورس</span>
                        <x-heroicon-m-arrow-left class="h-5 w-5 transition-transform group-hover:-translate-x-1" />
                    </div>
                    <span class="pointer-events-none absolute -top-6 -end-6 grid grid-cols-3 gap-1.5 opacity-25" aria-hidden="true">
                        @for ($p = 0; $p < 9; $p++)<i class="block h-5 w-5 rounded-[4px] {{ in_array($p, [1, 3, 5, 7]) ? 'bg-sky' : ($i === 1 ? 'bg-white' : 'bg-navy') }}"></i>@endfor
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ FEATURED COURSES ============ --}}
    <section class="bg-white py-16 lg:py-20" aria-labelledby="featured-title">
        <div class="cx-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">الأكثر طلباً</p>
                    <h2 id="featured-title" class="mt-4 text-3xl font-bold sm:text-4xl">كورسات مختارة</h2>
                </div>
                <a href="{{ route('courses.index') }}" class="btn btn-ghost btn-sm">كل الكورسات <x-heroicon-m-arrow-left class="h-4 w-4" /></a>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $course)
                    <x-course-card :course="$course" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ HOW IT WORKS (mirrors the CRM pipeline) ============ --}}
    <section id="how" class="cx-container scroll-mt-24 py-16 lg:py-24" aria-labelledby="how-title">
        <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p class="eyebrow">رحلة الطالب</p>
                <h2 id="how-title" class="mt-4 text-3xl font-bold sm:text-4xl">من التسجيل حتى الشهادة، كل خطوة متابَعة</h2>
                <p class="mt-5 leading-8 text-muted">لكل طالب ملف واحد عندنا. نعرف أين وصلت، وما مهامك هذا الأسبوع، ومتى يحتاج أحدنا للتواصل معك.</p>
                <a href="{{ route('register') }}" class="btn btn-primary mt-8">ابدأ الخطوة الأولى</a>
            </div>
            <ol class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['سجّل اهتمامك', 'املأ نموذج التسجيل، ويبدأ عدّاد الـ 24 ساعة لتواصل فريقنا معك.', 'heroicon-o-pencil-square'],
                    ['اختبار تحديد المستوى', 'نحدد موعداً مناسباً ونقيس مستواك لنضعك في المسار الصحيح.', 'heroicon-o-clipboard-document-check'],
                    ['خطة ومهام أسبوعية', 'لوحة "مهامي" تتحدث معك كل أسبوع، مع تذكيرات على واتساب.', 'heroicon-o-check-circle'],
                    ['شهادة قابلة للتحقق', 'أنهِ الدروس واجتز الاختبار النهائي لتصدر شهادتك تلقائياً.', 'heroicon-o-trophy'],
                ] as $n => [$title, $body, $icon])
                    <li class="card p-6">
                        <div class="flex items-center gap-4">
                            <span class="cx-step-num">{{ sprintf('%02d', $n + 1) }}</span>
                            <x-dynamic-component :component="$icon" class="h-6 w-6 text-sky" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-[0.95rem] leading-7 text-muted">{{ $body }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============ DEPARTMENTS ============ --}}
    <section class="cx-container pb-16" aria-labelledby="dept-title">
        <h2 id="dept-title" class="sr-only">الأقسام</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($departments as $department)
                <a href="{{ route('courses.index', ['department' => $department->slug]) }}" class="card card-hover flex items-center gap-4 p-5">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl font-mono text-sm font-semibold text-white" style="background: {{ $department->color }}">{{ strtoupper($department->language_code ?? '</>') }}</span>
                    <div>
                        <div class="font-semibold">{{ $department->name }}</div>
                        <div class="text-sm text-muted">{{ $department->courses_count }} كورس</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============ INSTRUCTORS ============ --}}
    <section class="bg-ink py-16 text-white lg:py-24" aria-labelledby="ins-title">
        <div class="cx-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow text-sky!">المحاضرون</p>
                    <h2 id="ins-title" class="mt-4 text-3xl font-bold sm:text-4xl">شاهد أسلوب الشرح قبل أن تسجّل</h2>
                    <p class="mt-3 max-w-xl text-slate-400">لكل محاضر فيديو تعريفي ومحاضرة تجريبية قصيرة، لتختار من يناسبك.</p>
                </div>
                <a href="{{ route('instructors.index') }}" class="btn btn-light btn-sm">كل المحاضرين</a>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($instructors as $profile)
                    <a href="{{ route('instructors.show', $profile) }}" class="group rounded-2xl bg-white/5 p-6 ring-1 ring-white/10 transition hover:bg-white/10">
                        <span class="grid h-16 w-16 place-items-center rounded-2xl bg-navy font-display text-xl font-semibold shadow-[4px_4px_0_var(--cx-sky)]">{{ $profile->user->initials() }}</span>
                        <h3 class="mt-5 font-semibold">{{ $profile->user->name }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-400">{{ $profile->headline }}</p>
                        <div class="mt-5 flex items-center justify-between text-sm">
                            <span class="font-mono text-sky">★ {{ $profile->rating }}</span>
                            <span class="inline-flex items-center gap-1 text-slate-300 group-hover:text-white"><x-heroicon-m-play-circle class="h-4 w-4" /> محاضرة تجريبية</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ TESTIMONIALS ============ --}}
    <section class="cx-container py-16 lg:py-24" aria-labelledby="rev-title">
        <p class="eyebrow">آراء الطلاب</p>
        <h2 id="rev-title" class="mt-4 text-3xl font-bold sm:text-4xl">النتيجة تتكلم</h2>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @foreach ($reviews as $review)
                <figure class="card flex flex-col p-6">
                    <div class="font-mono text-sky" aria-label="{{ $review->rating }} من 5">{{ str_repeat('■', $review->rating) }}<span class="text-line">{{ str_repeat('■', 5 - $review->rating) }}</span></div>
                    <blockquote class="mt-4 flex-1 text-[1.05rem] leading-8">«{{ $review->comment }}»</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3 border-t border-line pt-4">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-navy-50 text-sm font-semibold text-navy">{{ $review->user->initials() }}</span>
                        <div>
                            <div class="text-sm font-semibold">{{ $review->user->name }}</div>
                            <div class="text-xs text-muted">{{ $review->course?->title }}</div>
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="cx-container">
        <div class="relative overflow-hidden rounded-3xl bg-navy px-6 py-14 text-white sm:px-12 lg:px-16">
            <div class="cx-grid-bg pointer-events-none absolute inset-0 opacity-40" style="background-image: linear-gradient(to right, rgb(255 255 255 / .07) 1px, transparent 1px), linear-gradient(to bottom, rgb(255 255 255 / .07) 1px, transparent 1px);"></div>
            <div class="relative grid items-center gap-8 lg:grid-cols-[1.4fr_1fr]">
                <div>
                    <h2 class="text-3xl font-bold sm:text-4xl">جاهز تبدأ؟ اختبار المستوى علينا.</h2>
                    <p class="mt-4 max-w-xl text-white/80">سجّل الآن وسيتواصل معك أحد مستشارينا خلال 24 ساعة لتحديد موعد اختبار المستوى واقتراح المسار الأنسب لك.</p>
                </div>
                <div class="flex flex-wrap gap-3 lg:justify-end">
                    <a href="{{ route('register') }}" class="btn btn-light">سجّل اهتمامك الآن</a>
                    <a href="{{ route('courses.index', ['type' => 'recorded']) }}" class="btn border border-white/30 text-white hover:bg-white/10">ابدأ بكورس مسجل</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
