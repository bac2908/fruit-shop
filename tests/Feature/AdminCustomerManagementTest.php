<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Models\UserVoucher;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_customer_cannot_access_or_mutate_admin_customer_management(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.customers'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.customers.show', $customer))->assertForbidden();
        $this->actingAs($customer)->put(route('admin.customers.update', $customer), [
            'name' => 'Tên bị sửa',
        ])->assertForbidden();
    }

    public function test_admin_can_filter_export_and_open_customer_with_real_order_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'Khách VIP kiểm thử',
            'email' => 'vip-'.Str::lower(Str::random(8)).'@example.com',
        ]);
        $this->order($customer, ['status' => Order::STATUS_DONE, 'total' => 5100000]);
        $this->order($customer, ['status' => Order::STATUS_CANCELLED, 'total' => 900000]);

        $this->actingAs($admin)
            ->get(route('admin.customers', ['q' => $customer->email, 'segment' => 'vip']))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('5.100.000đ')
            ->assertSee('VIP');

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->email)
            ->assertSee('5.100.000đ')
            ->assertSee('Hạng thành viên');

        $this->actingAs($admin)
            ->get(route('admin.customers.export', ['q' => $customer->email]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_updates_only_safe_customer_profile_fields_and_writes_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $originalEmail = $customer->email;

        $this->actingAs($admin)
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Nguyễn Khách Hàng',
                'phone' => '0912345678',
                'birthday' => '1998-05-20',
                'gender' => 'female',
                'admin_note' => 'Khách ưu tiên giao hàng vào buổi sáng.',
                'email' => 'attacker@example.com',
                'role' => 'admin',
            ])
            ->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Nguyễn Khách Hàng', $customer->name);
        $this->assertSame('+84912345678', $customer->phone);
        $this->assertSame($originalEmail, $customer->email);
        $this->assertSame('customer', $customer->role);
        $this->assertSame('Khách ưu tiên giao hàng vào buổi sáng.', $customer->admin_note);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_customer_profile_updated',
        ]);
    }

    public function test_suspension_revokes_sessions_blocks_login_and_activation_restores_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'password' => bcrypt('Password123'),
            'session_version' => 1,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.customers.suspend', $customer), [
                'reason' => 'Tạm ngưng để kiểm tra dấu hiệu truy cập bất thường.',
            ])
            ->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertTrue($customer->isSuspended());
        $this->assertSame(2, $customer->session_version);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_customer_suspended',
        ]);

        Auth::logout();
        $this->post(route('login.post'), [
            'email' => $customer->email,
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)
            ->patch(route('admin.customers.activate', $customer))
            ->assertSessionHasNoErrors();

        Auth::logout();
        $this->post(route('login.post'), [
            'email' => $customer->email,
            'password' => 'Password123',
        ])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($customer->fresh());
    }

    public function test_admin_can_unlock_customer_and_revoke_all_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes(10),
            'session_version' => 3,
        ]);
        DB::table('secure_sessions')->insert([
            'user_id' => $customer->id,
            'session_id' => 'admin-customer-test-'.Str::random(20),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('admin.customers.unlock', $customer))->assertSessionHasNoErrors();
        $this->assertSame(0, $customer->fresh()->failed_login_attempts);
        $this->assertNull($customer->fresh()->locked_until);

        $this->actingAs($admin)->patch(route('admin.customers.sessions.revoke', $customer))->assertSessionHasNoErrors();
        $this->assertSame(4, $customer->fresh()->session_version);
        $this->assertDatabaseMissing('secure_sessions', ['user_id' => $customer->id]);
        $this->assertDatabaseHas('security_audit_log', [
            'user_id' => $admin->id,
            'action' => 'admin_customer_sessions_revoked',
        ]);
    }

    public function test_revoked_or_suspended_customer_session_is_rejected_on_the_next_request(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'session_version' => 2,
        ]);

        $this->actingAs($customer)
            ->withSession(['auth_session_version' => 1])
            ->get(route('account.profile'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $customer->forceFill([
            'account_status' => User::ACCOUNT_STATUS_SUSPENDED,
            'session_version' => 3,
        ])->save();

        $this->actingAs($customer)
            ->withSession(['auth_session_version' => 3])
            ->get(route('home'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_resend_verification_and_assign_a_valid_voucher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->unverified()->create(['role' => 'customer']);
        $coupon = Coupon::query()->create([
            'title' => 'Voucher chăm sóc khách hàng',
            'code' => 'CARE'.Str::upper(Str::random(8)),
            'type' => Coupon::TYPE_FIXED,
            'value' => 30000,
            'min_order_total' => 200000,
            'is_active' => true,
            'is_public' => false,
            'per_customer_limit' => 1,
            'used_count' => 0,
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.customers.verification', $customer))
            ->assertSessionHasNoErrors();
        Notification::assertSentTo($customer, CustomerVerifyEmailNotification::class);

        $this->actingAs($admin)
            ->post(route('admin.customers.vouchers.store', $customer), [
                'coupon_id' => $coupon->id,
                'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_vouchers', [
            'user_id' => $customer->id,
            'coupon_id' => $coupon->id,
        ]);
        $this->assertSame(1, UserVoucher::query()->where('user_id', $customer->id)->where('coupon_id', $coupon->id)->count());
    }

    public function test_admin_account_cannot_be_managed_through_customer_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.customers.show', $otherAdmin))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.customers.suspend', $otherAdmin), [
            'reason' => 'Không được phép sửa tài khoản quản trị tại đây.',
        ])->assertNotFound();
    }

    private function order(User $customer, array $overrides = []): Order
    {
        $code = 'DH-CUSTOMER-'.Str::upper(Str::random(10));

        return Order::query()->create(array_merge([
            'code' => $code,
            'public_token' => hash('sha256', $code.Str::random(20)),
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipping_address' => '74 Trần Thái Tông, Hà Nội',
            'subtotal' => 200000,
            'shipping_fee' => 25000,
            'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
            'discount_total' => 0,
            'total' => 225000,
            'status' => Order::STATUS_PENDING,
            'payment_method' => Order::PAYMENT_METHOD_COD,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
            'shipping_status' => Order::SHIPPING_STATUS_PENDING,
            'shipping_delivery_method' => Order::DELIVERY_METHOD_LOCAL_EXPRESS,
        ], $overrides));
    }
}
