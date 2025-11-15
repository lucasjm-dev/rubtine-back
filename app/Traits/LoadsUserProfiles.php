<?php

namespace App\Traits;

use App\Domains\Users\Models\User;

trait LoadsUserProfiles
{
    protected function withProfiles(User $user): User
    {
        return $user->load(User::availableTypes());
    }
}
