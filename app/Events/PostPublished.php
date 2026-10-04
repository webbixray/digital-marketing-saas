<?php

namespace App\Events;

use App\Models\SocialPost;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SocialPost $post,
        /**
         * @var array<int, array<string, mixed>>
         */
        public array $results = []
    ) {}

    /**
     * The channels the event broadcasts on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("agencies.{$this->post->agency_id}.posts"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'post.published';
    }

    /**
     * The data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->post->id,
            'platform' => $this->post->platform,
            'status' => $this->post->status,
            'published_at' => $this->post->published_at?->toIso8601String(),
        ];
    }
}
