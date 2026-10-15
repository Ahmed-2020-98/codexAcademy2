<x-layouts.auth title="إنشاء حساب">
    <h1 class="text-3xl font-bold">أنشئ حسابك</h1>
    <p class="mt-2 text-muted">حساب واحد لكل كورساتك ومهامك وشهاداتك.</p>

    <form method="POST" action="{{ route('signup') }}" class="mt-8 grid gap-5" novalidate>
        @csrf
        @foreach ([['name', 'الاسم بالكامل', 'text', 'name', 'rtl'], ['email', 'البريد الإلكتروني', 'email', 'email', 'ltr'], ['phone', 'رقم الهاتف / واتساب', 'tel', 'tel', 'ltr']] as [$field, $label, $type, $auto, $dir])
            <div class="field">
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" class="input" dir="{{ $dir }}" autocomplete="{{ $auto }}" required @error($field) aria-invalid="true" aria-describedby="{{ $field }}-err" @enderror>
                @error($field)<p id="{{ $field }}-err" class="field-error">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="field">
                <label for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" class="input" dir="ltr" autocomplete="new-password" required aria-describedby="pw-hint @error('password') pw-err @enderror">
                <p id="pw-hint" class="field-hint">8 أحرف على الأقل</p>
                @error('password')<p id="pw-err" class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">تأكيد كلمة المرور</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" dir="ltr" autocomplete="new-password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-full">إنشاء الحساب</button>
    </form>

    <p class="mt-8 text-center text-sm text-muted">لديك حساب بالفعل؟ <a href="{{ route('login') }}" class="font-semibold text-navy hover:underline">سجّل الدخول</a></p>
</x-layouts.auth>
