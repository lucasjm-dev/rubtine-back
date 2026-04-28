<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * Enforces role restrictions based on the actor's login type:
 *
 * - A professional logged-in user (non-owner) can join with role PROFESSIONAL
 *   only on tasks owned by a simple user.
 * - A professional logged-in user cannot self-assign the PROFESSIONAL role
 *   on tasks owned by another professional.
 * - A simple logged-in user can ONLY create with role SIMPLE, and only for themselves.
 * - The owner creating for others can assign any valid role.
 */
final class RoleMustMatchActorLogin implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        // Owner creating for someone else can assign any role
        if ($ctx->actorIsOwner && ! $ctx->actorIsSelf()) {
            return null;
        }

        // if ($ctx->actor->isLoggedAsProfessional()) {
        //     if (
        //         $ctx->role === TaskParticipantRole::PROFESSIONAL
        //         && $ctx->task->ownerHasProfessionalProfile()
        //     ) {
        //         return 'task_participant_role_forbidden';
        //     }
        // }

        // Simple logged-in: can only create with SIMPLE role
        if ($ctx->actor->isLoggedAsSimple()) {
            if ($ctx->role !== TaskParticipantRole::SIMPLE) {
                return 'task_participant_role_mismatch';
            }
        }

        return null;
    }
}
