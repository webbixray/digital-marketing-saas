<?php

namespace App\Enums;

enum AiActionType: string
{
    case GENERATE = 'generate';
    case REWRITE = 'rewrite';
    case SUMMARIZE = 'summarize';
    case TRANSLATE = 'translate';
    case IDEATE = 'ideate';
}
