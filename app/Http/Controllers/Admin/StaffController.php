<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPermissionService;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function store(
        Request $request,
        AdminPermissionService $permissions,
        SecurityAuditService $audit
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_role' => ['required', Rule::in(array_keys($permissions->roles()))],
            'password' => [
                'required',
                'max:72',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $staff = User::query()->create([
            'name' => trim($validated['name']),
            'email' => mb_strtolower(trim($validated['email'])),
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'admin_role' => $validated['admin_role'],
            'account_status' => User::ACCOUNT_STATUS_ACTIVE,
            'force_password_change' => true,
            'password_changed_at' => now(),
        ]);

        $this->audit($audit, $request, 'admin_staff_created', $staff);

        return back()->with('success', 'Đã tạo tài khoản nhân viên. Nhân viên phải đổi mật khẩu ở lần đăng nhập đầu.');
    }

    public function update(
        Request $request,
        User $staff,
        AdminPermissionService $permissions,
        SecurityAuditService $audit
    ): RedirectResponse {
        abort_unless($staff->isAdmin(), 404);
        $validated = $request->validate([
            'admin_role' => ['required', Rule::in(array_keys($permissions->roles()))],
            'account_status' => ['required', Rule::in([
                User::ACCOUNT_STATUS_ACTIVE,
                User::ACCOUNT_STATUS_SUSPENDED,
            ])],
        ]);

        if ($staff->is($request->user())
            && ($validated['admin_role'] !== $permissions->role($staff)
                || $validated['account_status'] !== User::ACCOUNT_STATUS_ACTIVE)) {
            return back()->withErrors(['staff' => 'Không thể tự hạ quyền hoặc khóa tài khoản đang đăng nhập.']);
        }

        if ($permissions->role($staff) === AdminPermissionService::ROLE_SUPER_ADMIN
            && $validated['admin_role'] !== AdminPermissionService::ROLE_SUPER_ADMIN
            && $this->superAdminCount() <= 1) {
            return back()->withErrors(['staff' => 'Hệ thống phải còn ít nhất một Chủ hệ thống.']);
        }

        $isSuspended = $validated['account_status'] === User::ACCOUNT_STATUS_SUSPENDED;
        $staff->forceFill([
            'admin_role' => $validated['admin_role'],
            'account_status' => $validated['account_status'],
            'suspended_at' => $isSuspended ? now() : null,
            'suspended_by' => $isSuspended ? $request->user()->id : null,
            'session_version' => ((int) $staff->session_version) + 1,
        ])->save();

        $this->audit($audit, $request, 'admin_staff_updated', $staff);

        return back()->with('success', 'Đã cập nhật quyền nhân viên.');
    }

    private function superAdminCount(): int
    {
        return User::query()
            ->where('role', 'admin')
            ->where('account_status', User::ACCOUNT_STATUS_ACTIVE)
            ->where(function ($query) {
                $query->where('admin_role', AdminPermissionService::ROLE_SUPER_ADMIN)
                    ->orWhereNull('admin_role');
            })
            ->count();
    }

    private function audit(
        SecurityAuditService $audit,
        Request $request,
        string $action,
        User $staff
    ): void {
        $audit->record($action, [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], [
            'staff_id' => $staff->id,
            'admin_role' => $staff->admin_role,
            'account_status' => $staff->account_status,
        ]);
    }
}
