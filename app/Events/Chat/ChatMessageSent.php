<?php

namespace App\Events\Chat;

use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var ChatMessage */
    protected $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message->load(['user', 'replyTo', 'reactions']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->message->channel_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array{
     *     id: int,
     *     channel_id: int,
     *     user_id: int,
     *     content: string,
     *     type: string,
     *     file_url: string|null,
     *     file_name: string|null,
     *     file_type: string|null,
     *     file_size: int|null,
     *     reply_to_id: int|null,
     *     is_edited: bool,
     *     is_deleted: bool,
     *     user: array{id: int, name: string, avatar: string},
     *     reactions: array<int, array{emoji: string, user_id: int}>,
     *     created_at: string
     * }
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'channel_id' => $this->message->channel_id,
            'user_id' => $this->message->user_id,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'file_url' => $this->message->file_url,
            'file_name' => $this->message->file_name,
            'file_type' => $this->message->file_type,
            'file_size' => $this->message->file_size,
            'reply_to_id' => $this->message->reply_to_id,
            'is_edited' => $this->message->is_edited,
            'is_deleted' => $this->message->is_deleted,
            'user' => [
                /** @var User|null $user */
                $user = $this->message->user,
                'id' => $user->id ?? 0,
                'name' => $user->name ?? 'Unknown',
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($user->name ?? 'Unknown').'&background=6366f1&color=fff&size=32',
            ],
            'reactions' => $this->message->reactions->map(
                function (Model $model, int|string $key): array {
                    /** @var ChatReaction $r */
                    $r = $model;

                    return [
                        'emoji' => $r->emoji,
                        'user_id' => $r->user_id,
                    ];
                }
            )->toArray(),
            'created_at' => $this->message->created_at?->toISOString() ?? '',
        ];
    }
}
