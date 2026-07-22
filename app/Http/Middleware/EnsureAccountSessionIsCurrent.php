<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSessionIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $storedVersion = (int) $request->session()->get('auth_session_version', 1);
        $currentVersion = max(1, (int) $user->session_version);
        $mustLogout = ($user->role === 'customer' && $user->isSuspended())
            || $storedVersion !== $currentVersion;

        if ($mustLogout) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', $user->isSuspended()
                    ? 'Tài khoản đang tạm ngưng. Vui lòng liên hệ cửa hàng để được hỗ trợ.'
                    : 'Phiên đăng nhập đã hết hiệu lực. Vui lòng đăng nhập lại.');
        }

        if (! $request->session()->has('auth_session_version')) {
            $request->session()->put('auth_session_version', $currentVersion);
        }

        return $next($request);
    }
}
