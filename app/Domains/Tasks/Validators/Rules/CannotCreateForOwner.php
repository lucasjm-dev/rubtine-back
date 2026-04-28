<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Validators\CreateParticipantContext;

final class CannotCreateForOwner implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->task->isOwnedBy($ctx->targetUser)) {
            return 'task_participant_target_is_owner';
        }

        return null;
    }
}
