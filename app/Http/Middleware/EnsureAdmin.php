<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || $user->isSuspended()) {
            abort(403);
        }

        if ($user->hasTwoFactorAuthentication()
            && (int) $request->session()->get('admin_2fa_user_id') !== (int) $user->id) {
            return redirect()->route('admin.2fa.challenge');
        }

        if ($user->force_password_change && ! $request->routeIs('admin.security.password.*')) {
            return redirect()->route('admin.security.password.edit')
                ->with('warning', 'Bạn cần đổi mật khẩu tạm thời trước khi tiếp tục.');
        }

        return $next($request);
    }
}
