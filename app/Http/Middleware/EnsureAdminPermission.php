<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        abort_unless($user && $user->hasAdminPermission($permission), 403, 'Bạn không có quyền thực hiện thao tác này.');

        return $next($request);
    }
}
