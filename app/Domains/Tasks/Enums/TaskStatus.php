<?php

namespace App\Domains\Tasks\Enums;

final class TaskStatus
{

    public const DRAFT = 'DRAFT';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const COMPLETED = 'COMPLETED';
    public const REMOVED = 'REMOVED';

    public static function values(): array
    {
        return [
            self::DRAFT,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::REMOVED,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
