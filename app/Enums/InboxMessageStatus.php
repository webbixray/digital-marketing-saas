<?php

namespace App\Enums;

enum InboxMessageStatus: string
{
    case UNREAD = 'unread';
    case READ = 'read';
    case TRIAGED = 'triaged';
    case REPLIED = 'replied';
    case ESCALATED = 'escalated';
    case ARCHIVED = 'archived';
}
