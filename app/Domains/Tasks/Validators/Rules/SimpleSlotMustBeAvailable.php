<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Tasks\Validators\CreateParticipantContext;

/**
 * When the participant profile is SIMPLE, there must not already be an accepted
 * simple participant on the task.
 */
final class SimpleSlotMustBeAvailable implements ParticipantRule
{
    public function validate(CreateParticipantContext $ctx): ?string
    {
        if ($ctx->participantProfile !== TaskParticipantProfile::SIMPLE) {
            return null;
        }

        $hasAcceptedSimple = $ctx->task->participants()
            ->where('task_role', TaskParticipantTaskRole::PARTICIPANT)
            ->where('participant_profile', TaskParticipantProfile::SIMPLE)
            ->where('status', TaskParticipantStatus::ACCEPTED)
            ->exists();

        if ($hasAcceptedSimple) {
            return 'task_participant_simple_slot_taken';
        }

        return null;
    }
}
