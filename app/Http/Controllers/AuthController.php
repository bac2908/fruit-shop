<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $email = strtolower(trim($credentials['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($this->isLocked($user)) {
            throw ValidationException::withMessages([
                'email' => 'Tai khoan dang bi khoa tam thoi. Vui long thu lai sau.',
            ]);
        }

        if (!Auth::attempt(['email' => $email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $this->recordFailedLogin($request, $email, $user);

            throw ValidationException::withMessages([
                'email' => 'Email hoac mat khau khong dung.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        if (!$user->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tai khoan nay khong co quyen quan tri.',
            ]);
        }

        $this->resetFailedLoginState($user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isLocked(?User $user): bool
    {
        if (!$user || !Schema::hasColumn('users', 'locked_until')) {
            return false;
        }

        return $user->locked_until && now()->lessThan($user->locked_until);
    }

    private function recordFailedLogin(Request $request, string $email, ?User $user): void
    {
        if (Schema::hasTable('failed_login_attempts')) {
            DB::table('failed_login_attempts')->insert([
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'attempt_time' => now(),
            ]);
        }

        if (!$user || !Schema::hasColumn('users', 'failed_login_attempts')) {
            return;
        }

        $attempts = (int) $user->failed_login_attempts + 1;
        $payload = ['failed_login_attempts' => $attempts];

        if ($attempts >= 5 && Schema::hasColumn('users', 'locked_until')) {
            $payload['locked_until'] = now()->addMinutes(15);
        }

        $user->forceFill($payload)->save();
    }

    private function resetFailedLoginState(User $user): void
    {
        $payload = [];

        if (Schema::hasColumn('users', 'failed_login_attempts')) {
            $payload['failed_login_attempts'] = 0;
        }

        if (Schema::hasColumn('users', 'locked_until')) {
            $payload['locked_until'] = null;
        }

        if (!empty($payload)) {
            $user->forceFill($payload)->save();
        }
    }
}
