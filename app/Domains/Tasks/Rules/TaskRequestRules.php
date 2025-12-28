<?php

namespace App\Domains\Tasks\Rules;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\ProfessionalUser;

final class TaskRequestRules
{
    public static function canCreate(Task $task, ProfessionalUser $professional): bool
    {
        return $task->subcategory_id === $professional->subcategory_id;
    }
}
