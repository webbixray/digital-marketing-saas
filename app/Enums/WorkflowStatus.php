<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
}
