<?php

namespace App\Domains\Tasks\Models\Pivots;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeForProfessional(Builder $query, ProfessionalUser $professional): Builder
    {
        return $query->where('professional_user_id', $professional->id);
    }

    public function scopeForSimpleUser(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'task',
            fn($q) =>
            $q->where('user_id', $user->id)
        );
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $status
            ? $query->where('status', $status)
            : $query;
    }
}
