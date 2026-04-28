<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * The target user must have the corresponding profile for the requested participant profile:
 * - SIMPLE  → must have a simpleUser record
 * - PROFESSIONAL → must have a professionalUser record
 */
final class TargetMustHaveRoleProfile implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->participantProfile === TaskParticipantProfile::SIMPLE) {
            $hasProfile = (bool) $ctx->targetUser->simpleUser;
        } elseif ($ctx->participantProfile === TaskParticipantProfile::PROFESSIONAL) {
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
