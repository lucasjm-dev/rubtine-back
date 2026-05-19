<?php

namespace App\Domains\Tasks\Enums;

final class TaskEventStatus
{

    public const PENDING = 'PENDING';
    public const TO_CONFIRM = 'TO_CONFIRM';
    public const CONFIRMED = 'CONFIRMED';
    public const COMPLETED = 'COMPLETED';
    public const CANCELLED = 'CANCELLED';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::TO_CONFIRM,
            self::CONFIRMED,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
