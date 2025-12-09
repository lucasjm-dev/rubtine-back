<?php

namespace App\Domains\Tasks\Enums;

enum TaskProfessionalUserStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case CANCELED = 'CANCELED';
    case REMOVED = 'REMOVED';
}
