<?php

namespace App\Domains\Tasks\Enums;

final class TaskParticipantProfile
{
    public const SIMPLE = 'SIMPLE';
    public const PROFESSIONAL = 'PROFESSIONAL';

    public static function values(): array
    {
        return [
            self::SIMPLE,
            self::PROFESSIONAL,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
