@props(['title' => null])
@php
    $nav = [
        ['learn.dashboard', 'الرئيسية', 'heroicon-o-home'],
        ['learn.courses', 'كورساتي', 'heroicon-o-book-open'],
        ['learn.tasks', 'مهامي', 'heroicon-o-check-circle'],
        ['learn.certificates', 'شهاداتي', 'heroicon-o-trophy'],
    ];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="min-h-screen antialiased" x-data="{ drawer: false }">
    <a href="#main" class="skip-link">تخطَّ إلى المحتوى</a>

    <div class="lg:grid lg:min-h-screen lg:grid-cols-[17rem_1fr]">
        {{-- Sidebar (desktop) / drawer (mobile) --}}
        <div x-show="drawer" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/50 lg:hidden" x-on:click="drawer = false"></div>
        <aside :class="drawer ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'" class="fixed inset-y-0 start-0 z-50 flex w-72 translate-x-full flex-col bg-ink text-white transition-transform duration-200 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-auto">
            <div class="flex h-20 items-center justify-between px-6">
                <a href="{{ route('home') }}"><x-brand.logo class="[&_.cx-logo__word]:text-white [&_.cx-logo__tag]:text-sky" /></a>
                <button type="button" class="grid h-10 w-10 place-items-center rounded-lg text-slate-300 lg:hidden" x-on:click="drawer = false" aria-label="إغلاق القائمة"><x-heroicon-o-x-mark class="h-6 w-6" /></button>
            </div>
            <nav class="cx-learn-nav grid gap-1 px-4" aria-label="لوحة الطالب">
                @foreach ($nav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" @if (request()->routeIs($route.'*') && ! ($route === 'learn.courses' && request()->routeIs('learn.checkout'))) aria-current="page" @endif>
                        <x-dynamic-component :component="$icon" class="h-5 w-5" /> {{ $label }}
                    </a>
                @endforeach
                <a href="{{ route('courses.index') }}"><x-heroicon-o-magnifying-glass class="h-5 w-5" /> استكشف الكورسات</a>
            </nav>

            @if ($code = $user->referralCode)
                <div class="mx-4 mt-8 rounded-xl bg-white/5 p-4 ring-1 ring-white/10" x-data="{ copied: false }">
                    <p class="text-xs text-slate-400">كود الإحالة الخاص بك — خصم {{ $code->discount_percent }}% لأصدقائك</p>
                    <button type="button" class="mt-2 flex w-full items-center justify-between gap-2 rounded-lg bg-navy px-3 py-2 font-mono text-sm font-semibold"
                            x-on:click="navigator.clipboard.writeText('{{ $code->code }}'); copied = true; setTimeout(() => copied = false, 1600)" aria-label="نسخ كود الإحالة">
                        <span dir="ltr">{{ $code->code }}</span>
                        <x-heroicon-o-clipboard-document class="h-4 w-4" x-show="!copied" />
                        <x-heroicon-s-check class="h-4 w-4 text-sky" x-show="copied" x-cloak />
                    </button>
                    <p class="mt-2 text-xs text-slate-400"><span class="font-mono text-sky">{{ $code->uses_count }}</span> استخدام حتى الآن</p>
                </div>
            @endif

            <div class="mt-auto border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-navy text-sm font-semibold">{{ $user->initials() }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
                        <div class="truncate text-xs text-slate-400">{{ $user->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="grid h-10 w-10 place-items-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white" aria-label="تسجيل الخروج" title="تسجيل الخروج"><x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5" /></button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur lg:hidden">
                <button type="button" class="grid h-11 w-11 place-items-center rounded-lg text-navy" x-on:click="drawer = true" aria-label="القائمة"><x-heroicon-o-bars-3 class="h-6 w-6" /></button>
                <x-brand.logo :tagline="false" />
            </header>
            <main id="main" class="px-4 py-8 sm:px-8 lg:py-10">
                <div class="mx-auto max-w-6xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
