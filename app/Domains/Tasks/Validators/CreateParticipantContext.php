<?php

namespace App\Domains\Tasks\Validators;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;

final class CreateParticipantContext
{
    public Task $task;
    public User $actor;
    public User $targetUser;
    public string $participantProfile;
    public bool $actorIsOwner;

    public function __construct(
        Task $task,
        User $actor,
        User $targetUser,
        string $participantProfile
    ) {
        $this->task = $task;
        $this->actor = $actor;
        $this->targetUser = $targetUser;
        $this->participantProfile = $participantProfile;
        $this->actorIsOwner = $task->isOwnedBy($actor);
    }

    public function actorIsSelf(): bool
    {
        return $this->actor->id === $this->targetUser->id;
    }
}
