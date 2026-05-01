<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * Enforces participant profile restrictions based on the actor's login type:
 *
 * - A professional logged-in user (non-owner) can join with participant profile PROFESSIONAL
 *   only on tasks owned by a simple user.
 * - A professional logged-in user cannot self-assign the PROFESSIONAL profile
 *   on tasks owned by another professional.
 * - A logged-in user creating for themselves must use the participant profile
 *   that matches their active login profile.
 * - The owner creating for others can assign any valid participant profile.
 */
final class RoleMustMatchActorLogin implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        // Owner creating for someone else can assign any participant profile
        if ($ctx->actorIsOwner && ! $ctx->actorIsSelf()) {
            return null;
        }

        $actorParticipantProfile = TaskParticipantProfile::fromUserLogin($ctx->actor);

        if (
            $actorParticipantProfile
            && $ctx->participantProfile !== $actorParticipantProfile
        ) {
            return 'task_participant_role_mismatch';
        }

        return null;
    }
}
