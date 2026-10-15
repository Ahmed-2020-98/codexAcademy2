<x-layouts.auth title="تسجيل الدخول">
    <h1 class="text-3xl font-bold">أهلاً بعودتك</h1>
    <p class="mt-2 text-muted">سجّل الدخول لمتابعة دروسك ومهامك.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 grid gap-5" novalidate>
        @csrf
        <div class="field">
            <label for="email">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" dir="ltr" autocomplete="email" required autofocus @error('email') aria-invalid="true" aria-describedby="email-err" @enderror>
            @error('email')<p id="email-err" class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="field" x-data="{ show: false }">
            <label for="password">كلمة المرور</label>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" class="input pe-12" dir="ltr" autocomplete="current-password" required>
                <button type="button" x-on:click="show = !show" class="absolute end-1 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-md text-muted hover:text-navy" :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'">
                    <x-heroicon-o-eye class="h-5 w-5" x-show="!show" />
                    <x-heroicon-o-eye-slash class="h-5 w-5" x-show="show" x-cloak />
                </button>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-line accent-[#0A5A9E]"> تذكرني
        </label>
        <button type="submit" class="btn btn-primary w-full">تسجيل الدخول</button>
    </form>

    <p class="mt-8 text-center text-sm text-muted">ليس لديك حساب؟ <a href="{{ route('signup') }}" class="font-semibold text-navy hover:underline">أنشئ حساباً</a></p>

    <div class="mt-10 rounded-xl border border-dashed border-line bg-white p-4 text-xs leading-6 text-muted">
        <p class="font-semibold text-ink">حسابات العرض التجريبي (كلمة المرور: <span class="font-mono">password</span>)</p>
        <ul class="mt-2 grid gap-0.5 font-mono" dir="ltr">
            <li>student@codex.academy — Student</li>
            <li>admin@codex.academy — Super Admin</li>
            <li>sales@codex.academy — Sales / Care</li>
            <li>instructor1@codex.academy — Instructor</li>
        </ul>
    </div>
</x-layouts.auth>
