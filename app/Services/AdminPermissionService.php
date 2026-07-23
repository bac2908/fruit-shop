<?php

namespace App\Services;

use App\Models\User;

class AdminPermissionService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CATALOG = 'catalog';
    public const ROLE_FULFILLMENT = 'fulfillment';
    public const ROLE_SUPPORT = 'support';
    public const ROLE_ANALYST = 'analyst';

    public function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Chủ hệ thống',
            self::ROLE_MANAGER => 'Quản lý cửa hàng',
            self::ROLE_CATALOG => 'Nhân viên sản phẩm',
            self::ROLE_FULFILLMENT => 'Nhân viên đơn hàng',
            self::ROLE_SUPPORT => 'Chăm sóc khách hàng',
            self::ROLE_ANALYST => 'Nhân viên báo cáo',
        ];
    }

    public function matrix(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => ['*'],
            self::ROLE_MANAGER => [
                'dashboard.view',
                'orders.manage',
                'catalog.manage',
                'content.manage',
                'customers.manage',
                'support.manage',
                'promotions.manage',
                'reports.view',
            ],
            self::ROLE_CATALOG => [
                'dashboard.view',
                'catalog.manage',
                'content.manage',
            ],
            self::ROLE_FULFILLMENT => [
                'dashboard.view',
                'orders.manage',
            ],
            self::ROLE_SUPPORT => [
                'dashboard.view',
                'customers.manage',
                'support.manage',
                'orders.view',
            ],
            self::ROLE_ANALYST => [
                'dashboard.view',
                'reports.view',
                'orders.view',
            ],
        ];
    }

    public function role(User $user): string
    {
        if (! $user->isAdmin()) {
            return '';
        }

        $role = trim((string) $user->admin_role);

        return array_key_exists($role, $this->roles()) ? $role : self::ROLE_SUPER_ADMIN;
    }

    public function allows(User $user, string $permission): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        $permissions = $this->matrix()[$this->role($user)] ?? [];

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true)
            || ($permission === 'orders.view' && in_array('orders.manage', $permissions, true));
    }
}
