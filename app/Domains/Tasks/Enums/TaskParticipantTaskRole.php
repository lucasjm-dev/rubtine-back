<?php

namespace App\Domains\Tasks\Enums;

final class TaskParticipantTaskRole
{
    public const OWNER = 'OWNER';
    public const PARTICIPANT = 'PARTICIPANT';

    public static function values(): array
    {
        return [
            self::OWNER,
            self::PARTICIPANT,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
