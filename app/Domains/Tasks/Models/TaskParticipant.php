<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Users\Models\User;
use App\Support\Users\ResolvesUserProfile;
use App\Support\Users\UserProfileMerger;
use App\Support\Users\UserProfiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskParticipant extends Model
{
    use ResolvesUserProfile;

    protected $table = 'task_participants';

    protected $fillable = [
        'task_id',
        'user_id',
        'task_role',
        'participant_profile',
        'status',
        'requested_by_user_id',
    ];

    protected $hidden = [
        'user',
        'requestedBy',
    ];

    protected $appends = ['profile', 'requested_by_profile'];

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

    /**
     * Returns a unified profile merging the participant's User fields with the
     * sub-profile declared by participant_profile.
     */
    public function getProfileAttribute(): ?array
    {
        if (! $this->relationLoaded('user') || ! $this->user) {
            return null;
        }

        $subProfile = $this->resolveProfileFor($this->user, $this->participant_profile);

        return UserProfileMerger::merge($this->user, $subProfile);
    }

    /**
     * Returns a unified profile for whoever requested this participant entry.
     * Detects the requester's profile type dynamically instead of assuming it
     * matches the participant's own profile.
     */
    public function getRequestedByProfileAttribute(): ?array
    {
        if (! $this->relationLoaded('requestedBy') || ! $this->requestedBy) {
            return null;
        }

        $subProfile = $this->resolveAnyProfileFor($this->requestedBy);

        return UserProfileMerger::merge($this->requestedBy, $subProfile);
    }

    public static function profileEagerLoads(string $userRelation): array
    {
        return array_merge(
            [$userRelation],
            UserProfiles::nestedRelations(
                $userRelation,
                TaskParticipantProfile::userProfileTypes()
            )
        );
    }

    public static function listRelations(): array
    {
        return array_merge(
            [
                'task',
                'task.participants' => fn($q) => $q->owners(),
            ],
            self::profileEagerLoads('task.participants.user'),
            self::profileEagerLoads('user'),
            self::profileEagerLoads('requestedBy')
        );
    }

    public function scopeWithListRelations(Builder $query): Builder
    {
        return $query->with(self::listRelations());
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
