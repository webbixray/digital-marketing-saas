<?php

namespace App\Events;

use App\Models\SocialPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(public SocialPost $post) {}
}
