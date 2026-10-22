<?php

namespace App\Services\AI\Gateway\Enums;

enum FinishReason: string
{
    case STOP = 'stop';
    case LENGTH = 'length';
    case CONTENT_FILTER = 'content_filter';
    case TOOL_CALLS = 'tool_calls';
    case FUNCTION_CALL = 'function_call';
    case ERROR = 'error';
    case OTHER = 'other';
}
