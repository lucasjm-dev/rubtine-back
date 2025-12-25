<?php

namespace App\Domains\Tasks\Models\Pivots;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\ProfessionalUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskRequest extends Pivot
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(ProfessionalUser::class, 'professional_user_id');
    }
}
