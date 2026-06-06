<?php

namespace App\Domains\Tasks\Enums;

final class EventNotificationType
{

    public const PUSH = 'PUSH';
    public const EMAIL = 'EMAIL';
    public const SMS = 'SMS';
    public const WHATSAPP = 'WHATSAPP';

    public static function values(): array
    {
        return [
            self::PUSH,
            self::EMAIL,
            self::SMS,
            self::WHATSAPP,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
