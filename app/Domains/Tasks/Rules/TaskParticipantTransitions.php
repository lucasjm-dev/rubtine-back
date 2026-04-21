<?php

namespace App\Domains\Tasks\Rules;

use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\TaskParticipant;

class TaskParticipantTransitions
{
    public static function canPending(TaskParticipant $taskParticipant): bool
    {
        return $taskParticipant->status === TaskParticipantStatus::CANCELED;
    }

    public static function canCancel(TaskParticipant $taskParticipant): bool
    {
        return $taskParticipant->status === TaskParticipantStatus::PENDING || $taskParticipant->status === TaskParticipantStatus::ACCEPTED;
    }

    public static function canAccept(TaskParticipant $taskParticipant): bool
    {
        return $taskParticipant->status === TaskParticipantStatus::PENDING || $taskParticipant->status === TaskParticipantStatus::REJECTED;
    }

    public static function canReject(TaskParticipant $taskParticipant): bool
    {
        return $taskParticipant->status === TaskParticipantStatus::PENDING || $taskParticipant->status === TaskParticipantStatus::ACCEPTED;
    }
}
