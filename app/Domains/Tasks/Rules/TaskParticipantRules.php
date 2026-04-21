<?php

namespace App\Domains\Tasks\Rules;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;

final class TaskParticipantRules
{
    public static function canCreate(Task $task, User $user, string $role): bool
    {
        if ($task->isOwnedBy($user)) {
            return false;
        }

        if ($role === TaskParticipantRole::SIMPLE) {
            return (bool) $user->simpleUser;
        }

        if ($role === TaskParticipantRole::PROFESSIONAL) {
            return (bool) $user->professionalUser
                && $task->subcategory_id === $user->professionalUser->subcategory_id;
        }

        return false;
    }
}
