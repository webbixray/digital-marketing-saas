<?php

namespace Tests\Feature\Auth;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_login_form(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_it_logs_in_user(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_it_validates_login(): void
    {
        $response = $this->post(route('login'), []);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_it_logs_out_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post(route('logout'));
        $response->assertRedirect();
        $this->assertGuest();
    }
}
