<?php

namespace App\Domains\Tasks\Validators;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;

final class CreateParticipantContext
{
    public Task $task;
    public User $actor;
    public User $targetUser;
    public string $role;
    public bool $actorIsOwner;

    public function __construct(
        Task $task,
        User $actor,
        User $targetUser,
        string $role
    ) {
        $this->task = $task;
        $this->actor = $actor;
        $this->targetUser = $targetUser;
        $this->role = $role;
        $this->actorIsOwner = $task->isOwnedBy($actor);
    }

    public function actorIsSelf(): bool
    {
        return $this->actor->id === $this->targetUser->id;
    }
}
