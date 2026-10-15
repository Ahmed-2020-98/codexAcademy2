<x-layouts.site title="سجّل اهتمامك">
    <section class="cx-grid-bg">
        <div class="cx-container grid gap-12 py-12 lg:grid-cols-[0.85fr_1.15fr] lg:py-16">
            <div class="lg:pt-8">
                <p class="eyebrow">ابدأ من هنا</p>
                <h1 class="mt-4 text-3xl font-bold leading-tight sm:text-[2.6rem]">احجز اختبار تحديد المستوى المجاني</h1>
                <p class="mt-5 leading-8 text-muted">املأ البيانات، وسيتواصل معك فريقنا خلال 24 ساعة كحد أقصى لتحديد موعد الاختبار وترشيح المسار والمجموعة المناسبة.</p>
                <ol class="mt-10 grid gap-5">
                    @foreach (['تسجيل البيانات' => 'دقيقة واحدة', 'تواصل فريق المتابعة' => 'خلال 24 ساعة', 'اختبار المستوى' => 'في موعد يناسبك', 'التسجيل الرسمي' => 'وتبدأ لوحة مهامك'] as $step => $hint)
                        <li class="flex items-center gap-4">
                            <span class="cx-step-num">{{ $loop->iteration }}</span>
                            <div><div class="font-semibold">{{ $step }}</div><div class="text-sm text-muted">{{ $hint }}</div></div>
                        </li>
                    @endforeach
                </ol>
            </div>
            <livewire:lead-form />
        </div>
    </section>
</x-layouts.site>
