<?php

namespace App\Domains\Tasks\Enums;

enum TaskStatus: string
{
    case DRAFT = 'DRAFT';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case REMOVED = 'REMOVED';
}
