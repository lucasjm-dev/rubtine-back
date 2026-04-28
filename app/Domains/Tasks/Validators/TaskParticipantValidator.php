<?php

namespace App\Domains\Tasks\Validators;

use App\Domains\Tasks\Validators\Rules\ActorMustBeOwnerOrSelf;
use App\Domains\Tasks\Validators\Rules\CannotCreateForOwner;
use App\Domains\Tasks\Validators\Rules\ParticipantRule;
use App\Domains\Tasks\Validators\Rules\ProfessionalMustMatchSubcategory;
use App\Domains\Tasks\Validators\Rules\RoleMustMatchActorLogin;
use App\Domains\Tasks\Validators\Rules\SimpleSlotMustBeAvailable;
use App\Domains\Tasks\Validators\Rules\TargetMustHaveRoleProfile;

class TaskParticipantValidator
{
    /**
     * Ordered pipeline of rules for creating a participant.
     * Add new rules here to extend validation.
     *
     * @var class-string<ParticipantRule>[]
     */
    private array $createRules = [
        CannotCreateForOwner::class,
        ActorMustBeOwnerOrSelf::class,
        RoleMustMatchActorLogin::class,
        TargetMustHaveRoleProfile::class,
        ProfessionalMustMatchSubcategory::class,
        SimpleSlotMustBeAvailable::class,
    ];

    /**
     * Run all creation rules against the given context.
     *
     * @return string|null null if all rules pass, error code if any fails
     */
    public function validateCreate(CreateParticipantContext $ctx): ?string
    {
        foreach ($this->createRules as $ruleClass) {
            /** @var ParticipantRule $rule */
            $rule = new $ruleClass();
            $error = $rule->validate($ctx);

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }
}
