<?php

namespace App\Events;

use App\Models\SocialPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SocialPost $post,
        public readonly string $errorMessage = '',
        public readonly int $attemptNumber = 0,
    ) {}
}
