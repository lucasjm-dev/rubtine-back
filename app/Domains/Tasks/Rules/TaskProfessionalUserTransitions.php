<?php

namespace App\Domains\Tasks\Rules;

use App\Domains\Tasks\Enums\TaskProfessionalUserStatus;

final class TaskProfessionalUserTransitions
{
    private const TRANSITIONS = [
        TaskProfessionalUserStatus::PENDING => [
            TaskProfessionalUserStatus::ACCEPTED,
            TaskProfessionalUserStatus::REJECTED,
            TaskProfessionalUserStatus::CANCELED,
        ],
        TaskProfessionalUserStatus::ACCEPTED => [
            TaskProfessionalUserStatus::REMOVED,
        ],
        TaskProfessionalUserStatus::CANCELED => [
            TaskProfessionalUserStatus::PENDING
        ],
    ];

    public static function canTransition(
        string $from,
        string $to
    ): bool {

        // Don't change same status
        if ($from === $to) {
            return false;
        }

        return in_array(
            $to,
            self::TRANSITIONS[$from] ?? [],
            true
        );
    }
}
