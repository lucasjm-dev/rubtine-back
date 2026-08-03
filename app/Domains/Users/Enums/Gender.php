<?php

namespace App\Domains\Users\Enums;

final class Gender
{

    public const MALE = 'male';
    public const FEMALE = 'female';

    public static function values(): array
    {
        return [
            self::MALE,
            self::FEMALE,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
