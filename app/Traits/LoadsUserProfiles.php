<?php

namespace App\Traits;

use App\Domains\Users\Models\User;
use App\Support\Users\UserProfiles;

trait LoadsUserProfiles
{
    protected function loadProfiles(User $user): User
    {
        return $user->load(
            UserProfiles::relationNames($user->getAvailableTypes())
        );
    }
}
