<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_registration_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_it_registers_new_user(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agency_name' => 'Test Agency',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('agencies', ['name' => 'Test Agency']);
    }

    public function test_it_validates_registration(): void
    {
        $response = $this->post(route('register'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'agency_name']);
    }

    public function test_it_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'agency_name' => 'Test Agency',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
