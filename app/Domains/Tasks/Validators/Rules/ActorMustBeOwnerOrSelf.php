<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Validators\CreateParticipantContext;

final class ActorMustBeOwnerOrSelf implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->actorIsOwner) {
            return null;
        }

        if (! $ctx->actorIsSelf()) {
            return 'task_participant_forbidden';
        }

        return null;
    }
}
