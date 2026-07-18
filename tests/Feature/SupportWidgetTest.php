<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupportWidgetTest extends TestCase
{
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
}
