<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_reset_link_is_sent_to_the_registered_users_real_email(): void
    {
        $user = User::factory()->create([
            'email' => 'registered-customer@example.test',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'other-customer@example.test',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => '  REGISTERED-CUSTOMER@example.test  ',
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo(
            $user,
            CustomerResetPasswordNotification::class,
            fn ($notification, array $channels) => $channels === ['mail']
                && $user->routeNotificationFor('mail', $notification) === $user->email
        );
        Notification::assertNotSentTo($otherUser, CustomerResetPasswordNotification::class);
    }

    public function test_unknown_email_gets_the_same_generic_response_without_sending_mail(): void
    {
        $this->post(route('password.email'), [
            'email' => 'not-registered@example.test',
        ])->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_valid_reset_token_changes_the_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewStrongPass123', $user->fresh()->password));
    }
}
