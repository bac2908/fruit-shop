<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPermissionService;
use App\Services\SecurityAuditService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(
        Request $request,
        SettingService $settings,
        AdminPermissionService $permissions
    ): View {
        return view('admin.settings', [
            'settings' => $settings->all(),
            'adminRoles' => $permissions->roles(),
            'currentAdminRole' => $permissions->role($request->user()),
            'staffMembers' => User::query()
                ->where('role', 'admin')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(
        Request $request,
        SettingService $settings,
        SecurityAuditService $audit
    ): RedirectResponse {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'min:2', 'max:120'],
            'store_hotline' => ['required', 'string', 'regex:/^[0-9+().\s-]{8,20}$/'],
            'store_email' => ['required', 'email', 'max:255'],
            'store_address' => ['required', 'string', 'min:5', 'max:500'],
            'display_timezone' => ['required', 'in:Asia/Ho_Chi_Minh'],
            'shipping_free_threshold' => ['required', 'integer', 'min:0', 'max:100000000'],
            'shipping_default_fee' => ['required', 'integer', 'min:0', 'max:5000000'],
            'shipping_remote_surcharge' => ['required', 'integer', 'min:0', 'max:5000000'],
            'low_stock_default_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $payload = array_merge($validated, [
            'payment_cod_enabled' => $request->boolean('payment_cod_enabled'),
            'payment_bank_enabled' => $request->boolean('payment_bank_enabled'),
            'payment_momo_enabled' => $request->boolean('payment_momo_enabled'),
            'email_order_placed_enabled' => $request->boolean('email_order_placed_enabled'),
            'email_order_confirmed_enabled' => $request->boolean('email_order_confirmed_enabled'),
            'email_order_cancelled_enabled' => $request->boolean('email_order_cancelled_enabled'),
            'low_stock_alert_enabled' => $request->boolean('low_stock_alert_enabled'),
        ]);

        if (! $payload['payment_cod_enabled']
            && ! $payload['payment_bank_enabled']
            && ! $payload['payment_momo_enabled']) {
            return back()
                ->withInput()
                ->withErrors(['payment_methods' => 'Phải bật ít nhất một phương thức thanh toán.']);
        }

        $before = $settings->all();
        $settings->putMany($payload);
        $changed = collect($payload)
            ->filter(fn ($value, $key) => ($before[$key] ?? null) != $value)
            ->keys()
            ->values()
            ->all();

        $audit->record('admin_settings_updated', [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], ['changed_keys' => $changed]);

        return back()->with('success', 'Đã lưu cài đặt và áp dụng cho storefront.');
    }
}
