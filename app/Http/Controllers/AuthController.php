<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        $request->session()->regenerate();

        // Staff go to the back office, students to their portal.
        return Auth::user()->hasAnyRole(Role::staff())
            ? redirect()->intended('/admin')
            : redirect()->intended(route('learn.dashboard'));
    }

    public function showSignup(): View
    {
        return view('auth.signup');
    }

    public function signup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);
        $user->assignRole(Role::Student->value);
        ReferralCode::issueFor($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('learn.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
