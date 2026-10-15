<div class="mx-auto max-w-4xl">
    <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-navy"><x-heroicon-m-arrow-right class="h-4 w-4" /> العودة للكورس</a>
    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">إتمام الشراء</h1>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="card p-6" aria-labelledby="pay-title">
            <h2 id="pay-title" class="font-semibold">الدفع الإلكتروني</h2>
            <p class="mt-1 text-sm text-muted">بطاقات بنكية ومحافظ إلكترونية. يتم تفعيل الكورس فور نجاح الدفع.</p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-3 rounded-xl border-[1.5px] border-navy bg-navy-50 p-4">
                    <x-heroicon-o-credit-card class="h-6 w-6 text-navy" />
                    <div><div class="font-semibold">بطاقة بنكية</div><div class="text-xs text-muted">Visa · Mastercard · Meeza</div></div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border-[1.5px] border-line p-4 opacity-70">
                    <x-heroicon-o-device-phone-mobile class="h-6 w-6 text-navy" />
                    <div><div class="font-semibold">محفظة إلكترونية</div><div class="text-xs text-muted">تُفعّل مع بوابة الدفع</div></div>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-dashed border-sky/60 bg-sky-soft p-4 text-sm text-navy-deep">
                <strong>وضع التجربة:</strong> بوابة الدفع الحالية تجريبية وتعتمد العملية مباشرة. يتم ربط Paymob / Moyasar عند الإطلاق.
            </div>

            @error('payment')<p class="field-error mt-4" role="alert">{{ $message }}</p>@enderror

            <button type="button" wire:click="pay" wire:loading.attr="disabled" class="btn btn-primary mt-6 w-full">
                <x-heroicon-o-lock-closed class="h-4 w-4" />
                <span wire:loading.remove wire:target="pay">ادفع {{ number_format($quote['total'], 2) }} ج.م</span>
                <span wire:loading wire:target="pay">جارٍ تنفيذ الدفع…</span>
            </button>
        </section>

        <aside class="card h-fit p-6" aria-label="ملخص الطلب">
            <x-course-cover :course="$course" />
            <h2 class="mt-4 font-semibold leading-snug">{{ $course->title }}</h2>

            <form wire:submit="applyCode" class="mt-5">
                <label for="ref-code" class="text-sm font-semibold">كود الإحالة / الخصم</label>
                @if ($appliedCode)
                    <div class="mt-2 flex items-center justify-between rounded-lg bg-green-50 px-3 py-2.5 text-sm text-green-800">
                        <span class="font-mono font-semibold" dir="ltr">{{ $appliedCode }}</span>
                        <button type="button" wire:click="removeCode" class="font-semibold hover:underline">إزالة</button>
                    </div>
                @else
                    <div class="mt-2 flex gap-2">
                        <input id="ref-code" type="text" wire:model="code" class="input font-mono uppercase" dir="ltr" placeholder="CDX-XXXX" @error('code') aria-invalid="true" aria-describedby="code-err" @enderror>
                        <button type="submit" class="btn btn-ghost shrink-0">تطبيق</button>
                    </div>
                    @error('code')<p id="code-err" class="field-error mt-1.5">{{ $message }}</p>@enderror
                @endif
            </form>

            <dl class="mt-6 grid gap-2.5 border-t border-line pt-5 text-sm">
                <div class="flex justify-between"><dt class="text-muted">سعر الكورس</dt><dd class="font-mono">{{ number_format($quote['gross'], 2) }}</dd></div>
                @if ($quote['discount'] > 0)
                    <div class="flex justify-between text-green-700"><dt>خصم الإحالة ({{ $quote['code']->discount_percent }}%)</dt><dd class="font-mono">−{{ number_format($quote['discount'], 2) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-line pt-3 text-base font-semibold"><dt>الإجمالي</dt><dd class="font-mono">{{ number_format($quote['total'], 2) }} ج.م</dd></div>
            </dl>
        </aside>
    </div>
</div>
