<?php

namespace App\Domains\Tasks\Enums;

final class EventNotificationStatus
{

    public const PENDING = 'PENDING';
    public const SENT = 'SENT';
    public const FAILED = 'FAILED';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::SENT,
            self::FAILED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
