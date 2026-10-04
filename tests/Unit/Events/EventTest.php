<?php

namespace Tests\Unit\Events;

use App\Events\PostPublished;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_published_event_can_be_instantiated(): void
    {
        $post = SocialPost::factory()->create();
        $event = new PostPublished($post);
        $this->assertInstanceOf(PostPublished::class, $event);
        $this->assertEquals($post->id, $event->post->id);
    }

    public function test_event_broadcasts_on_channel(): void
    {
        $post = SocialPost::factory()->create();
        $event = new PostPublished($post);
        $this->assertNotEmpty($event->broadcastOn());
    }
}
