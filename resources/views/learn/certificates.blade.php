<x-layouts.learn title="شهاداتي">
    <h1 class="text-2xl font-bold sm:text-3xl">شهاداتي</h1>
    <p class="mt-2 text-muted">تصدر الشهادة تلقائياً عند إتمام كل الدروس واجتياز الاختبار النهائي.</p>

    <div class="mt-8 grid gap-6 sm:grid-cols-2">
        @forelse ($certificates as $certificate)
            <article class="card relative overflow-hidden p-6">
                <div class="absolute -end-4 -top-4 grid grid-cols-3 gap-1.5 opacity-80" aria-hidden="true">
                    @for ($i = 0; $i < 9; $i++)<i class="block h-5 w-5 rounded-[4px] {{ in_array($i, [0, 4, 8]) ? 'bg-sky' : (in_array($i, [2, 6]) ? 'bg-navy' : '') }}"></i>@endfor
                </div>
                <x-heroicon-o-trophy class="h-9 w-9 text-navy" />
                <h2 class="mt-4 text-lg font-semibold">{{ $certificate->course->title }}</h2>
                <p class="mt-1 text-sm text-muted">صدرت في {{ $certificate->issued_at->translatedFormat('d F Y') }}</p>
                <p class="mt-4 font-mono text-sm" dir="ltr">{{ $certificate->serial }}</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('certificates.show', $certificate) }}" target="_blank" class="btn btn-primary btn-sm"><x-heroicon-o-eye class="h-4 w-4" /> عرض وطباعة</a>
                    <button type="button" class="btn btn-ghost btn-sm" x-data="{ ok: false }" x-on:click="navigator.clipboard.writeText('{{ route('certificates.show', $certificate) }}'); ok = true; setTimeout(() => ok = false, 1500)">
                        <x-heroicon-o-link class="h-4 w-4" /> <span x-text="ok ? 'تم النسخ' : 'نسخ رابط التحقق'">نسخ رابط التحقق</span>
                    </button>
                </div>
            </article>
        @empty
            <div class="card col-span-full grid place-items-center gap-3 px-6 py-16 text-center">
                <x-heroicon-o-trophy class="h-10 w-10 text-sky" />
                <p class="font-semibold">لا توجد شهادات بعد</p>
                <p class="text-sm text-muted">أكمل دروس أحد الكورسات المسجلة واجتز الاختبار النهائي.</p>
            </div>
        @endforelse
    </div>
</x-layouts.learn>
