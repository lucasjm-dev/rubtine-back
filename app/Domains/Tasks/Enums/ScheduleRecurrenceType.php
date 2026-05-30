<?php

namespace App\Domains\Tasks\Enums;

final class ScheduleRecurrenceType
{

    public const ONCE = 'ONCE';
    public const DAILY = 'DAILY';
    public const WEEKLY = 'WEEKLY';
    public const MONTHLY = 'MONTHLY';

    public static function values(): array
    {
        return [
            self::ONCE,
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
