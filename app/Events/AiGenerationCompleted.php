<?php

namespace App\Events;

use App\Models\AiContentLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiGenerationCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public AiContentLog $log) {}
}
