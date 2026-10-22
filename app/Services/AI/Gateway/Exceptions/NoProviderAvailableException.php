<?php

namespace App\Services\AI\Gateway\Exceptions;

use RuntimeException;

class NoProviderAvailableException extends RuntimeException
{
    public function __construct(string $message = 'No AI provider is currently available.', int $code = 503, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
