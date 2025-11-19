<?php

namespace App\Traits;

use App\Domains\Users\Models\User;

trait LoadsUserProfiles
{
    protected function loadProfiles(User $user): User
    {
        return $user->load($user->getAvailableTypes());
    }
}
