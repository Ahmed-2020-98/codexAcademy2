<div>
    @if ($lead)
        <div class="card p-8 text-center sm:p-12" role="status">
            <x-brand.mark animated class="mx-auto w-20" />
            <h2 class="mt-8 text-2xl font-bold">تم استلام تسجيلك يا {{ \Illuminate\Support\Str::before($lead->name, ' ') }} 🎉</h2>
            <p class="mx-auto mt-3 max-w-md leading-7 text-muted">سيتواصل معك أحد مستشارينا عبر الهاتف أو واتساب لتحديد موعد اختبار المستوى قبل انتهاء العدّاد.</p>
            <div x-data="{ due: {{ $lead->sla_due_at->timestamp }}, now: Math.floor(Date.now()/1000), init() { setInterval(() => this.now = Math.floor(Date.now()/1000), 1000) }, get t() { const s = Math.max(0, this.due - this.now); return [Math.floor(s/3600), Math.floor(s%3600/60), s%60].map(n => String(n).padStart(2,'0')).join(':') } }"
                 class="mx-auto mt-8 inline-flex items-center gap-3 rounded-xl bg-navy px-6 py-4 text-white shadow-[4px_4px_0_var(--cx-sky)]">
                <x-heroicon-o-clock class="h-6 w-6 text-sky" />
                <span class="font-mono text-2xl font-semibold" dir="ltr" x-text="t">24:00:00</span>
            </div>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('courses.index') }}" class="btn btn-ghost">تصفّح الكورسات</a>
                <a href="{{ route('signup') }}" class="btn btn-primary">أنشئ حسابك على المنصة</a>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="card grid gap-5 p-6 sm:p-8" novalidate>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="field sm:col-span-2">
                    <label for="lf-name">الاسم بالكامل</label>
                    <input id="lf-name" type="text" wire:model.blur="name" class="input" autocomplete="name" required @error('name') aria-invalid="true" aria-describedby="lf-name-err" @enderror>
                    @error('name')<p id="lf-name-err" class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="lf-phone">رقم الهاتف / واتساب</label>
                    <input id="lf-phone" type="tel" wire:model.blur="phone" class="input" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="+20 10 0000 0000" required @error('phone') aria-invalid="true" aria-describedby="lf-phone-err" @enderror>
                    @error('phone')<p id="lf-phone-err" class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="lf-email">البريد الإلكتروني <span class="font-normal text-muted">(اختياري)</span></label>
                    <input id="lf-email" type="email" wire:model.blur="email" class="input" dir="ltr" autocomplete="email" @error('email') aria-invalid="true" aria-describedby="lf-email-err" @enderror>
                    @error('email')<p id="lf-email-err" class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <fieldset class="grid gap-3">
                <legend class="text-sm font-semibold">طريقة الدراسة المفضلة</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($types as $type)
                        <label @class(['flex cursor-pointer items-center gap-3 rounded-xl border-[1.5px] p-3.5 transition-colors', 'border-navy bg-navy-50' => $study_mode === $type->value, 'border-line bg-white hover:border-navy/40' => $study_mode !== $type->value])>
                            <input type="radio" wire:model.live="study_mode" value="{{ $type->value }}" class="sr-only">
                            <x-dynamic-component :component="$type->getIcon()" class="h-5 w-5 text-navy" />
                            <span class="font-medium">{{ $type->getLabel() }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="field">
                    <label for="lf-dept">القسم</label>
                    <select id="lf-dept" wire:model.live="department_id" class="input">
                        <option value="">اختر القسم</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="lf-course">الكورس <span class="font-normal text-muted">(اختياري)</span></label>
                    <select id="lf-course" wire:model="course_id" class="input">
                        <option value="">لست متأكداً — ساعدوني في الاختيار</option>
                        @foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->title }} — {{ $c->type->getLabel() }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="lf-notes">ملاحظات <span class="font-normal text-muted">(اختياري)</span></label>
                <textarea id="lf-notes" wire:model.blur="notes" rows="3" class="input" placeholder="مثال: أحتاج المستوى B1 للسفر خلال 4 أشهر"></textarea>
                @error('notes')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-full sm:w-auto sm:justify-self-start" wire:loading.attr="disabled" wire:loading.class="is-loading">
                <span wire:loading.remove wire:target="submit">إرسال التسجيل</span>
                <span wire:loading wire:target="submit">جارٍ الإرسال…</span>
            </button>
            <p class="field-hint">بالإرسال، يبدأ عدّاد الـ 24 ساعة لتواصل فريقنا معك. لن نشارك بياناتك مع أي طرف.</p>
        </form>
    @endif
</div>
