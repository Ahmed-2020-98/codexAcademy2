@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.partials.head')
</head>
<body class="min-h-screen antialiased">
    <a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>

    <header x-data="{ open: false, scrolled: false }" x-on:scroll.window="scrolled = window.scrollY > 8"
            :class="scrolled ? 'bg-white/90 shadow-[0_1px_0_rgb(15_23_42/0.06)] backdrop-blur' : 'bg-transparent'"
            class="no-print sticky top-0 z-40 transition-colors duration-200">
        <div class="cx-container flex h-16 items-center gap-6 lg:h-20">
            <a href="{{ route('home') }}" aria-label="أكاديمية كودكس — الرئيسية" class="shrink-0"><x-brand.logo /></a>

            <nav aria-label="القائمة الرئيسية" class="hidden flex-1 items-center gap-1 lg:flex">
                @foreach ([['home', 'الرئيسية'], ['courses.index', 'الكورسات'], ['instructors.index', 'المحاضرون']] as [$route, $label])
                    <a href="{{ route($route) }}" @class(['rounded-lg px-3.5 py-2 text-[0.95rem] font-medium transition-colors', 'text-navy bg-navy-50' => request()->routeIs($route.'*'), 'text-graphite hover:text-navy' => ! request()->routeIs($route.'*')])>{{ $label }}</a>
                @endforeach
                <a href="{{ route('home') }}#how" class="rounded-lg px-3.5 py-2 text-[0.95rem] font-medium text-graphite hover:text-navy">كيف نعمل</a>
            </nav>

            <div class="ms-auto hidden items-center gap-3 lg:flex">
                @auth
                    <a href="{{ auth()->user()->canAccessPanel(filament()->getPanel('admin')) ? url('/admin') : route('learn.dashboard') }}" class="btn btn-primary btn-sm">
                        <x-heroicon-o-squares-2x2 class="h-4 w-4" /> لوحتي
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">احجز اختبار المستوى</a>
                @endauth
            </div>

            <button type="button" class="ms-auto grid h-11 w-11 place-items-center rounded-lg text-navy lg:hidden" x-on:click="open = !open" :aria-expanded="open" aria-controls="mobile-nav" aria-label="القائمة">
                <x-heroicon-o-bars-3 class="h-6 w-6" x-show="!open" />
                <x-heroicon-o-x-mark class="h-6 w-6" x-show="open" x-cloak />
            </button>
        </div>

        <div id="mobile-nav" x-show="open" x-cloak x-transition.opacity class="border-t border-line bg-white lg:hidden">
            <nav class="cx-container grid gap-1 py-4" aria-label="القائمة">
                <a href="{{ route('home') }}" class="rounded-lg px-3 py-3 font-medium hover:bg-surface">الرئيسية</a>
                <a href="{{ route('courses.index') }}" class="rounded-lg px-3 py-3 font-medium hover:bg-surface">الكورسات</a>
                <a href="{{ route('instructors.index') }}" class="rounded-lg px-3 py-3 font-medium hover:bg-surface">المحاضرون</a>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @auth
                        <a href="{{ route('learn.dashboard') }}" class="btn btn-primary col-span-2">لوحتي</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost">تسجيل الدخول</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">احجز اختبار</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="no-print mt-24 bg-ink text-slate-300">
        <div class="cx-container grid gap-10 py-14 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-brand.logo class="[&_.cx-logo__word]:text-white [&_.cx-logo__tag]:text-sky" />
                <p class="mt-4 max-w-md text-sm leading-7 text-slate-400">منصة تعليمية متكاملة تجمع الكورسات المسجلة والتفاعلية والحضورية، مع متابعة شخصية لكل طالب من أول يوم وحتى الشهادة.</p>
            </div>
            <div>
                <h2 class="font-display text-sm font-semibold text-white">الأكاديمية</h2>
                <ul class="mt-4 grid gap-2.5 text-sm">
                    <li><a class="hover:text-sky" href="{{ route('courses.index') }}">كل الكورسات</a></li>
                    <li><a class="hover:text-sky" href="{{ route('instructors.index') }}">المحاضرون</a></li>
                    <li><a class="hover:text-sky" href="{{ route('register') }}">اختبار تحديد المستوى</a></li>
                </ul>
            </div>
            <div>
                <h2 class="font-display text-sm font-semibold text-white">تواصل معنا</h2>
                <ul class="mt-4 grid gap-2.5 text-sm">
                    <li><a class="hover:text-sky" href="https://wa.me/{{ ltrim(\App\Models\Setting::get('academy_whatsapp'), '+') }}" dir="ltr">{{ \App\Models\Setting::get('academy_whatsapp') }}</a></li>
                    <li>القاهرة — مدينة نصر</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="cx-container flex flex-wrap items-center justify-between gap-3 py-5 text-xs text-slate-500">
                <span>© {{ date('Y') }} Codex Academy. جميع الحقوق محفوظة.</span>
                <span class="font-mono" dir="ltr">&lt;learn · build · grow /&gt;</span>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
