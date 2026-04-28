<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * The target user must have the corresponding profile for the requested role:
 * - SIMPLE  → must have a simpleUser record
 * - PROFESSIONAL → must have a professionalUser record
 */
final class TargetMustHaveRoleProfile implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->role === TaskParticipantRole::SIMPLE) {
            $hasProfile = (bool) $ctx->targetUser->simpleUser;
        } elseif ($ctx->role === TaskParticipantRole::PROFESSIONAL) {
            $hasProfile = (bool) $ctx->targetUser->professionalUser;
        } else {
            $hasProfile = false;
        }

        if (! $hasProfile) {
            return 'task_participant_target_missing_profile';
        }

        return null;
    }
}
