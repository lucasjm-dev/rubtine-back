<?php

namespace App\Domains\Tasks\Enums;

final class TaskParticipantRole
{
    public const OWNER = 'OWNER';
    public const SIMPLE = 'SIMPLE';
    public const PROFESSIONAL = 'PROFESSIONAL';

    public static function values(): array
    {
        return [
            self::OWNER,
            self::SIMPLE,
            self::PROFESSIONAL,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
