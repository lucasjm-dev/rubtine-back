<?php

namespace App\Support\Users;

use App\Domains\Users\Models\CompanyUser;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

final class UserProfiles
{
    private const DEFINITIONS = [
        User::TYPE_SIMPLE => [
            'relation' => 'simpleUser',
            'response_key' => 'simple_user',
            'model' => SimpleUser::class,
        ],
        User::TYPE_PROFESSIONAL => [
            'relation' => 'professionalUser',
            'response_key' => 'professional_user',
            'model' => ProfessionalUser::class,
        ],
        User::TYPE_COMPANY => [
            'relation' => 'companyUser',
            'response_key' => 'company_user',
            'model' => CompanyUser::class,
        ],
    ];

    public static function all(): array
    {
        return self::DEFINITIONS;
    }

    public static function types(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function relationForType(?string $type): ?string
    {
        return $type && isset(self::DEFINITIONS[$type])
            ? self::DEFINITIONS[$type]['relation']
            : null;
    }

    public static function responseKeyForType(?string $type): ?string
    {
        return $type && isset(self::DEFINITIONS[$type])
            ? self::DEFINITIONS[$type]['response_key']
            : null;
    }

    public static function modelForType(?string $type): ?string
    {
        return $type && isset(self::DEFINITIONS[$type])
            ? self::DEFINITIONS[$type]['model']
            : null;
    }

    public static function relationNames(?array $types = null): array
    {
        $types = $types === null ? self::types() : $types;

        return array_values(array_filter(array_map(function (string $type) {
            return self::relationForType($type);
        }, $types)));
    }

    public static function nestedRelations(string $baseRelation, ?array $types = null): array
    {
        return array_map(function (string $relation) use ($baseRelation) {
            return $baseRelation . '.' . $relation;
        }, self::relationNames($types));
    }

    public static function hasProfile(User $user, ?string $type): bool
    {
        $relation = self::relationForType($type);

        return $relation ? (bool) $user->{$relation} : false;
    }

    public static function hasAnyProfile(User $user, ?array $types = null): bool
    {
        foreach (self::relationNames($types) as $relation) {
            if ($user->{$relation}) {
                return true;
            }
        }

        return false;
    }

    public static function loadedProfile(User $user, ?string $type): ?Model
    {
        $relation = self::relationForType($type);

        if (! $relation || ! $user->relationLoaded($relation)) {
            return null;
        }

        return $user->getRelation($relation);
    }

    public static function firstLoadedProfile(User $user, ?array $types = null): ?Model
    {
        foreach (($types === null ? self::types() : $types) as $type) {
            $profile = self::loadedProfile($user, $type);

            if ($profile) {
                return $profile;
            }
        }

        return null;
    }
}
