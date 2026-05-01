<?php

namespace App\Support\Users;

use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserProfileMerger
{
    /**
     * Keys stripped from the sub-profile model before merging,
     * to avoid colliding with User fields.
     */
    private const STRIP_KEYS = ['id', 'user_id', 'laravel_through_key'];

    /**
     * Merges a User's base fields with its specific sub-profile model
     * (ProfessionalUser, SimpleUser, etc.) into a single flat array.
     *
     * The sub-profile fields are merged ON TOP of the user fields, so
     * profile-specific data (birth_date, about_me, subcategory) is always
     * present, while internal/redundant keys are stripped.
     *
     * @param  User        $user          The base user
     * @param  Model|null  $profileModel  The resolved sub-profile model, or null
     * @return array
     */
    public static function merge(User $user, ?Model $profileModel = null): array
    {
        $base = $user->attributesToArray();

        if (! $profileModel) {
            return $base;
        }

        $extra = array_diff_key(
            $profileModel->attributesToArray(),
            array_flip(self::STRIP_KEYS)
        );

        return array_merge($base, $extra);
    }
}
