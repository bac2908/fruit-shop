<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionMailCheckCommandTest extends TestCase
{
    public function test_mail_check_accepts_a_safe_local_transport(): void
    {
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'shop@example.test');
        config()->set('mail.from.name', 'Thế Giới Trái Cây');
        config()->set('app.url', 'http://localhost');

        $this->artisan('mail:check')->assertSuccessful();
    }

    public function test_mail_check_rejects_an_invalid_sender_address(): void
    {
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'not-an-email');
        config()->set('mail.from.name', 'Thế Giới Trái Cây');

        $this->artisan('mail:check')->assertFailed();
    }

    public function test_mail_check_can_send_a_smoke_message_without_network_in_tests(): void
    {
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'shop@example.test');
        config()->set('mail.from.name', 'Thế Giới Trái Cây');

        $this->artisan('mail:check', [
            '--send' => 'owner@example.test',
        ])->assertSuccessful();
    }
}
