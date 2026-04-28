<?php

namespace App\Domains\Tasks\Validators\Rules;

use App\Domains\Tasks\Validators\CreateParticipantContext;

interface ParticipantRule
{
    /**
     * Validate the given context.
     *
     * @return string|null null if valid, error code string if invalid
     */
    public function validate(CreateParticipantContext $ctx): ?string;
}
