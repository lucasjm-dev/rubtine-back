<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * When the role is PROFESSIONAL, the target user's professional subcategory
 * must match the task's subcategory.
 */
final class ProfessionalMustMatchSubcategory implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->role !== TaskParticipantRole::PROFESSIONAL) {
            return null;
        }

        $professional = $ctx->targetUser->professionalUser;

        if (! $professional || $professional->subcategory_id !== $ctx->task->subcategory_id) {
            return 'task_participant_subcategory_mismatch';
        }

        return null;
    }
}
