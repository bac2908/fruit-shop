<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupportWidgetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_storefront_uses_focused_quick_actions_and_a_functional_support_widget(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('data-floating-actions', false)
            ->assertSee('aria-label="Gọi hotline"', false)
            ->assertSee('aria-label="Nhắn Zalo"', false)
            ->assertSee('aria-label="Xem địa chỉ cửa hàng"', false)
            ->assertSee('id="supportWidgetToggle"', false)
            ->assertSee('id="supportWidget"', false)
            ->assertSee('data-support-topic="shipping"', false)
            ->assertSee('data-support-topic="payment"', false)
            ->assertSee('data-support-topic="orders"', false)
            ->assertSee('data-support-topic="returns"', false)
            ->assertDontSee('icon-item youtube', false)
            ->assertDontSee('icon-item instagram', false)
            ->assertDontSee('icon-item tiktok', false);
    }

    public function test_authenticated_customer_receives_a_direct_order_history_action(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response
            ->assertOk()
            ->assertSee('data-orders-authenticated="true"', false)
            ->assertSee(route('account.profile', ['tab' => 'orders']), false)
            ->assertSee("hasAuthenticatedCustomer ? 'Mở đơn hàng của tôi'", false);
    }
}
