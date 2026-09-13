<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_system_status_page_requires_auth(): void
    {
        $response = $this->get(route('system.status'));
        $response->assertStatus(302);
    }

    public function test_system_status_page_returns_200_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('system.status'));
        $response->assertStatus(200);
    }

    public function test_system_backup_page_requires_auth(): void
    {
        $response = $this->get(route('system.backup.index'));
        $response->assertStatus(302);
    }

    public function test_system_backup_page_returns_200(): void
    {
        $response = $this->actingAs($this->user)->get(route('system.backup.index'));
        $response->assertStatus(200);
    }

    public function test_backup_create_returns_success(): void
    {
        $response = $this->actingAs($this->user)->post(route('system.backup.store'));
        $response->assertStatus(302);
    }
}
