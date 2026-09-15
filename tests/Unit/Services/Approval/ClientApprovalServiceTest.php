<?php

namespace Tests\Unit\Services\Approval;

use App\Models\Agency;
use App\Models\Client;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Approval\ClientApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClientApprovalService();
    }

    public function test_submit_for_approval_updates_post(): void
    {
        $agency = Agency::factory()->create();
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'approval_status' => null,
        ]);

        $this->service->submitForApproval($post, $client->id);

        $post->refresh();
        $this->assertEquals('pending', $post->approval_status);
        $this->assertEquals($client->id, $post->client_id);
    }

    public function test_approve_updates_post(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'approval_status' => 'pending',
        ]);

        $this->service->approve($post, $user->id, 'Looks good!');

        $post->refresh();
        $this->assertEquals('approved', $post->approval_status);
        $this->assertEquals($user->id, $post->approved_by);
        $this->assertEquals('Looks good!', $post->approval_notes);
        $this->assertNotNull($post->approved_at);
    }

    public function test_reject_updates_post(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'approval_status' => 'pending',
        ]);

        $this->service->reject($post, $user->id, 'Needs more engaging content.');

        $post->refresh();
        $this->assertEquals('rejected', $post->approval_status);
        $this->assertEquals($user->id, $post->approved_by);
        $this->assertEquals('Needs more engaging content.', $post->approval_notes);
    }

    public function test_is_pending_returns_true_for_pending_post(): void
    {
        $post = SocialPost::factory()->create(['approval_status' => 'pending']);
        $this->assertTrue($this->service->isPending($post));
    }

    public function test_is_approved_returns_true_for_approved_post(): void
    {
        $post = SocialPost::factory()->create(['approval_status' => 'approved']);
        $this->assertTrue($this->service->isApproved($post));
    }

    public function test_is_rejected_returns_true_for_rejected_post(): void
    {
        $post = SocialPost::factory()->create(['approval_status' => 'rejected']);
        $this->assertTrue($this->service->isRejected($post));
    }
}
