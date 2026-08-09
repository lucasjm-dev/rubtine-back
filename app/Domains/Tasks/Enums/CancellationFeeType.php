<?php

namespace App\Domains\Tasks\Enums;

/**
 * Tipo de cargo por cancelación fuera de término.
 *
 *  - FULL_SESSION: se abona la sesión completa (sin valor asociado).
 *  - PERCENTAGE:   se abona un porcentaje de la sesión (valor = 0..100).
 *  - FIXED_AMOUNT: se abona un monto fijo (valor = monto en la moneda local).
 */
final class CancellationFeeType
{

    public const FULL_SESSION = 'FULL_SESSION';
    public const PERCENTAGE = 'PERCENTAGE';
    public const FIXED_AMOUNT = 'FIXED_AMOUNT';

    public static function values(): array
    {
        return [
            self::FULL_SESSION,
            self::PERCENTAGE,
            self::FIXED_AMOUNT,
        ];
    }

    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    public static function requiresValue(?string $value): bool
    {
        return in_array($value, [self::PERCENTAGE, self::FIXED_AMOUNT], true);
    }
}
