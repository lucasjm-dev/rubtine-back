<?php

namespace App\Domains\Tasks\Models\Pivots;

use App\Domains\Tasks\Enums\TaskProfessionalUserStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\ProfessionalUser;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskProfessional extends Pivot
{
    protected $table = 'task_professional_user';

    protected $casts = [];

    public static function findFor(
        Task $task,
        ProfessionalUser $professionalUser
    ): ?self {
        return self::query()
            ->where('task_id', $task->id)
            ->where('professional_user_id', $professionalUser->id)
            ->first();
    }
}
