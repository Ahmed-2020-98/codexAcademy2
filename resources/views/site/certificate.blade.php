<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('layouts.partials.head', ['title' => 'شهادة إتمام — '.$certificate->user->name])
    <style>
        @page { size: A4 landscape; margin: 0; }
        @media print { .cert-wrap { padding: 0 !important; } .cert { box-shadow: none !important; } }
    </style>
</head>
<body class="cx-grid-bg min-h-screen">
    <div class="cert-wrap mx-auto max-w-5xl px-4 py-10">
        <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
            <span class="chip chip-green"><x-heroicon-m-check-badge class="h-4 w-4" /> شهادة صحيحة وصادرة من أكاديمية كودكس</span>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm"><x-heroicon-o-printer class="h-4 w-4" /> طباعة / حفظ PDF</button>
        </div>

        <article class="cert relative aspect-[1.414/1] overflow-hidden rounded-2xl bg-white p-[6%] shadow-[0_30px_60px_-30px_rgb(10_90_158/0.45)]">
            <div class="absolute inset-3 rounded-xl border-2 border-navy/15"></div>
            <div class="absolute -end-10 -top-10 grid grid-cols-4 gap-2 opacity-90" aria-hidden="true">
                @for ($i = 0; $i < 16; $i++)<i class="block h-8 w-8 rounded-md {{ in_array($i, [0, 5, 10, 15, 3, 6]) ? 'bg-sky' : (in_array($i, [1, 4, 11]) ? 'bg-navy' : 'bg-transparent') }}"></i>@endfor
            </div>

            <div class="relative flex h-full flex-col">
                <x-brand.logo class="[&_.cx-logo__mark]:w-12 [&_.cx-logo__word]:text-2xl" />
                <div class="my-auto text-center">
                    <p class="font-mono text-xs tracking-[0.35em] text-navy" dir="ltr">CERTIFICATE OF COMPLETION</p>
                    <h1 class="mt-4 text-[clamp(1.4rem,3vw,2.3rem)] font-bold">شهادة إتمام</h1>
                    <p class="mt-6 text-muted">تشهد أكاديمية كودكس بأن</p>
                    <p class="mt-3 font-display text-[clamp(1.8rem,4.5vw,3.2rem)] font-bold text-navy">{{ $certificate->user->name }}</p>
                    <p class="mt-4 text-muted">قد أتمّ بنجاح متطلبات كورس</p>
                    <p class="mt-2 text-[clamp(1.1rem,2.4vw,1.6rem)] font-semibold">«{{ $certificate->course->title }}»</p>
                </div>
                <div class="flex items-end justify-between gap-6 text-sm">
                    <div>
                        <div class="text-xs text-muted">تاريخ الإصدار</div>
                        <div class="font-semibold">{{ $certificate->issued_at->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-display text-lg font-semibold">{{ $certificate->course->instructor?->name }}</div>
                        <div class="mt-1 border-t border-line pt-1 text-xs text-muted">المحاضر</div>
                    </div>
                    <div class="text-end">
                        <div class="text-xs text-muted">رقم التحقق</div>
                        <div class="font-mono font-semibold" dir="ltr">{{ $certificate->serial }}</div>
                    </div>
                </div>
            </div>
        </article>
        <p class="no-print mt-6 text-center text-sm text-muted">رابط التحقق: <span class="font-mono" dir="ltr">{{ route('certificates.show', $certificate) }}</span></p>
    </div>
</body>
</html>
