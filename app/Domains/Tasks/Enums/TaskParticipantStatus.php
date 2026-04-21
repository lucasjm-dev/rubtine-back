<?php

namespace App\Domains\Tasks\Enums;

final class TaskParticipantStatus
{

    public const PENDING = 'PENDING';
    public const ACCEPTED = 'ACCEPTED';
    public const REJECTED = 'REJECTED';
    public const CANCELED = 'CANCELED';
    public const REMOVED = 'REMOVED';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::ACCEPTED,
            self::REJECTED,
            self::CANCELED,
            self::REMOVED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
