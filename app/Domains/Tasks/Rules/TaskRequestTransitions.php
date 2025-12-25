<?php

namespace App\Domains\Tasks\Rules;

use App\Domains\Tasks\Enums\TaskRequestStatus;
use App\Domains\Tasks\Models\Pivots\TaskRequest;

class TaskRequestTransitions
{
    public static function canPending(TaskRequest $taskRequest): bool
    {
        return $taskRequest->status === TaskRequestStatus::CANCELED;
    }

    public static function canCancel(TaskRequest $taskRequest): bool
    {
        return $taskRequest->status === TaskRequestStatus::PENDING;
    }

    public static function canAccept(TaskRequest $taskRequest): bool
    {
        return $taskRequest->status === TaskRequestStatus::PENDING;
    }

    public static function canReject(TaskRequest $taskRequest): bool
    {
        return $taskRequest->status === TaskRequestStatus::PENDING;
    }
}
