<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_comments(): void
    {
        Comment::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->getJson('/comments');
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_creates_comment(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $data = [
            'commentable_type' => Campaign::class,
            'commentable_id' => $campaign->id,
            'body' => 'Great campaign!',
        ];
        $response = $this->post('/comments', $data);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('comments', ['body' => 'Great campaign!', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_comment_creation(): void
    {
        $response = $this->post('/comments', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['commentable_type', 'commentable_id', 'body']);
    }

    public function test_it_prevents_accessing_other_agency_comments(): void
    {
        $otherAgency = Agency::factory()->create();
        $comment = Comment::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->getJson("/comments/{$comment->id}");
        $response->assertStatus(403);
    }

    public function test_it_deletes_comment(): void
    {
        $campaign = Campaign::factory()->create(['agency_id' => $this->agency->id]);
        $comment = Comment::factory()->create([
            'agency_id' => $this->agency->id,
            'commentable_type' => Campaign::class,
            'commentable_id' => $campaign->id,
        ]);
        $response = $this->delete("/comments/{$comment->id}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
