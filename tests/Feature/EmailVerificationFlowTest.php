<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_registration_sends_verification_email_and_redirects_to_notice(): void
    {
        $email = 'verify-' . uniqid() . '@example.test';

        $response = $this->post(route('register.post'), [
            'name' => 'Khách xác minh',
            'email' => $email,
            'phone' => '0912345678',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'terms' => '1',
        ]);

        $user = User::query()->where('email', $email)->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, CustomerVerifyEmailNotification::class);
    }

    public function test_unverified_customer_is_blocked_from_checkout(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->get(route('checkout'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_signed_link_verifies_the_authenticated_customer(): void
    {
        $user = User::factory()->unverified()->create(['role' => 'customer']);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('home'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
