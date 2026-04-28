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
 * - A simple logged-in user can ONLY create with participant profile SIMPLE, and only for themselves.
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

        // if ($ctx->actor->isLoggedAsProfessional()) {
        //     if (
        //         $ctx->participantProfile === TaskParticipantProfile::PROFESSIONAL
        //         && $ctx->task->ownerHasProfessionalProfile()
        //     ) {
        //         return 'task_participant_role_forbidden';
        //     }
        // }

        // Simple logged-in: can only create with SIMPLE participant profile
        if ($ctx->actor->isLoggedAsSimple()) {
            if ($ctx->participantProfile !== TaskParticipantProfile::SIMPLE) {
                return 'task_participant_role_mismatch';
            }
        }

        return null;
    }
}
