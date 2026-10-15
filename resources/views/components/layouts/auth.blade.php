@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="min-h-screen antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        <aside class="relative hidden overflow-hidden bg-navy p-12 text-white lg:flex lg:flex-col" aria-hidden="true">
            <div class="absolute inset-0" style="background-image: linear-gradient(to right, rgb(255 255 255 / .06) 1px, transparent 1px), linear-gradient(to bottom, rgb(255 255 255 / .06) 1px, transparent 1px); background-size: 32px 32px;"></div>
            <a href="{{ route('home') }}" class="relative"><x-brand.logo class="[&_.cx-logo__word]:text-white [&_.cx-logo__tag]:text-sky" /></a>
            <div class="relative my-auto">
                <x-brand.mark animated class="w-40 [&_.cx-mark__navy_rect]:fill-white!" />
                <p class="mt-10 max-w-sm font-display text-3xl font-semibold leading-snug">كل أسبوع خطوة. وكل خطوة متابَعة.</p>
                <p class="mt-4 max-w-sm text-white/70">لوحة مهامك، دروسك، جلساتك وشهاداتك في مكان واحد.</p>
            </div>
            <p class="relative font-mono text-xs text-white/50" dir="ltr">&lt;learn · build · grow /&gt;</p>
        </aside>
        <main id="main" class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-10 inline-block lg:hidden"><x-brand.logo /></a>
                {{ $slot }}
            </div>
        </main>
    </div>
    @livewireScripts
</body>
</html>
