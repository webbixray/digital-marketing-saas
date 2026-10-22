<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlowQueryDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $query,
        /** @var array<string, mixed> */
        public array $bindings,
        public float $time,
        public string $connection,
        public ?string $file = null,
        public ?int $line = null,
    ) {}
}
