<?php

namespace App\Support\Users;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ResolvesUserProfile
{
    protected function resolveProfileFor(User $user, ?string $participantProfile): ?Model
    {
        return UserProfiles::loadedProfile(
            $user,
            TaskParticipantProfile::userProfileTypeFor($participantProfile)
        );
    }

    protected function resolveAnyProfileFor(User $user): ?Model
    {
        return UserProfiles::firstLoadedProfile(
            $user,
            TaskParticipantProfile::userProfileTypes()
        );
    }
}
