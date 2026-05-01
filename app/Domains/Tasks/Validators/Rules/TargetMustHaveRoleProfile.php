<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Validators\CreateParticipantContext;
use App\Support\Users\UserProfiles;

/**
 * The target user must have the user profile that backs the requested
 * participant profile.
 */
final class TargetMustHaveRoleProfile implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        $hasProfile = UserProfiles::hasProfile(
            $ctx->targetUser,
            TaskParticipantProfile::userProfileTypeFor($ctx->participantProfile)
        );

        if (! $hasProfile) {
            return 'task_participant_target_missing_profile';
        }

        return null;
    }
}
