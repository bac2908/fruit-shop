<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleOAuthConfigurationTest extends TestCase
{
    public function test_google_redirect_uses_the_configured_docker_callback(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', 'http://localhost:8080/auth/google/callback');

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame(
            'http://localhost:8080/auth/google/callback',
            $query['redirect_uri'] ?? null
        );
        $this->assertNotEmpty($query['state'] ?? null);
    }

    public function test_google_cancel_returns_to_login_with_a_friendly_message(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied');

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'error',
                'Bạn đã hủy đăng nhập Google hoặc Google không cấp quyền cho website.'
            );
    }
}
