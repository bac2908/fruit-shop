<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function editPassword(): View
    {
        return view('admin.security.password');
    }

    public function updatePassword(Request $request, SecurityAuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'max:72',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'force_password_change' => false,
            'remember_token' => null,
            'session_version' => ((int) $user->session_version) + 1,
        ])->save();

        $request->session()->put('session_version', $user->session_version);
        $audit->record('admin_password_changed', [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ]);

        return redirect()->route('admin.settings')->with('success', 'Đã đổi mật khẩu quản trị.');
    }
}
