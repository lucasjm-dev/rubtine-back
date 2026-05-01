<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * When the participant profile is PROFESSIONAL, the target user's professional subcategory
 * must match the task's subcategory.
 */
final class ProfessionalMustMatchSubcategory implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->participantProfile !== TaskParticipantProfile::PROFESSIONAL) {
            return null;
        }

        $professionalRelation = TaskParticipantProfile::relationFor(TaskParticipantProfile::PROFESSIONAL);
        $professional = $professionalRelation
            ? $ctx->targetUser->{$professionalRelation}
            : null;

        if (! $professional || $professional->subcategory_id !== $ctx->task->subcategory_id) {
            return 'task_participant_subcategory_mismatch';
        }

        return null;
    }
}
