<?php

namespace App\Domains\Tasks\Enums;

use App\Domains\Users\Models\User;
use App\Support\Users\UserProfiles;

final class TaskParticipantProfile
{
    public const SIMPLE = 'SIMPLE';
    public const PROFESSIONAL = 'PROFESSIONAL';

    private const USER_PROFILE_TYPES = [
        self::SIMPLE => User::TYPE_SIMPLE,
        self::PROFESSIONAL => User::TYPE_PROFESSIONAL,
    ];

    public static function values(): array
    {
        return array_keys(self::USER_PROFILE_TYPES);
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    public static function userProfileTypeFor(?string $participantProfile): ?string
    {
        return $participantProfile && isset(self::USER_PROFILE_TYPES[$participantProfile])
            ? self::USER_PROFILE_TYPES[$participantProfile]
            : null;
    }

    public static function fromUserProfileType(?string $userProfileType): ?string
    {
        if (! $userProfileType) {
            return null;
        }

        $participantProfile = array_search($userProfileType, self::USER_PROFILE_TYPES, true);

        return $participantProfile === false ? null : $participantProfile;
    }

    public static function fromUserLogin(User $user): ?string
    {
        return self::fromUserProfileType($user->loggedAs());
    }

    public static function userProfileTypes(?array $participantProfiles = null): array
    {
        $participantProfiles = $participantProfiles === null
            ? self::values()
            : $participantProfiles;

        return array_values(array_filter(array_map(function (string $participantProfile) {
            return self::userProfileTypeFor($participantProfile);
        }, $participantProfiles)));
    }

    public static function relationFor(?string $participantProfile): ?string
    {
        return UserProfiles::relationForType(
            self::userProfileTypeFor($participantProfile)
        );
    }
}
