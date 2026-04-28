<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskParticipant extends Model
{
    protected $table = 'task_participants';

    protected $fillable = [
        'task_id',
        'user_id',
        'task_role',
        'participant_profile',
        'status',
        'requested_by_user_id',
    ];

    protected $casts = [];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public static function findForTaskAndUser(
        Task $task,
        User $user,
        ?string $taskRole = null,
        ?string $participantProfile = null
    ): ?self {
        $query = self::query()
            ->where('task_id', $task->id)
            ->where('user_id', $user->id);

        if ($taskRole) {
            $query->where('task_role', $taskRole);
        }

        if ($participantProfile) {
            $query->where('participant_profile', $participantProfile);
        }

        return $query->first();
    }

    public function scopeForTaskRole(Builder $query, string $taskRole): Builder
    {
        return $query->where('task_role', $taskRole);
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->forTaskRole(TaskParticipantTaskRole::OWNER);
    }

    public function scopeParticipants(Builder $query): Builder
    {
        return $query->forTaskRole(TaskParticipantTaskRole::PARTICIPANT);
    }

    public function scopeProfessionals(Builder $query): Builder
    {
        return $query->where('participant_profile', TaskParticipantProfile::PROFESSIONAL);
    }

    public function scopeSimpleUsers(Builder $query): Builder
    {
        return $query->where('participant_profile', TaskParticipantProfile::SIMPLE);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForTaskOwner(Builder $query, User $user): Builder
    {
        return $query->whereHas('task.participants', fn($q) => $q->owners()->where('user_id', $user->id));
    }

    public function scopeRelevantToUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->forUser($user)
                ->orWhere(function (Builder $ownedQuery) use ($user) {
                    $ownedQuery->forTaskOwner($user);
                });
        });
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $status
            ? $query->where('status', $status)
            : $query;
    }

    public function isOwner(): bool
    {
        return $this->task_role === TaskParticipantTaskRole::OWNER;
    }

    public function hasProfessionalProfile(): bool
    {
        return $this->participant_profile === TaskParticipantProfile::PROFESSIONAL;
    }

    public function hasSimpleProfile(): bool
    {
        return $this->participant_profile === TaskParticipantProfile::SIMPLE;
    }
}
