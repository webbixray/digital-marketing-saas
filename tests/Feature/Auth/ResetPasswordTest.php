<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_reset_form(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'test-token']));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.reset');
    }

    public function test_it_shows_link_request_form(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.email');
    }

    public function test_it_validates_reset(): void
    {
        $response = $this->post(route('password.update'), []);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    public function test_it_validates_email_request(): void
    {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors(['email']);
    }
}
