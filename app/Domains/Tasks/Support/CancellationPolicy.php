<?php

namespace App\Domains\Tasks\Support;

use App\Domains\Tasks\Enums\CancellationFeeType;

/**
 * Value object de la política de cancelación de un evento.
 *
 * Encapsula los tres campos que la definen (aviso mínimo en horas, tipo de
 * cargo y valor asociado) y el formateo a texto humano que se inyecta en el
 * template de WhatsApp ({{cancellation_notice}} y {{cancellation_fee}}).
 *
 * Modelo de herencia: en eventos y schedules los campos en null significan
 * "hereda". La cadena evento → schedule → settings del profesional → default
 * genérico se resuelve recién al enviar el recordatorio (ver resolve()).
 */
final class CancellationPolicy
{
    public const DEFAULT_NOTICE_HOURS = 24;
    public const DEFAULT_FEE_TYPE = CancellationFeeType::FULL_SESSION;

    public const FIELDS = [
        'cancellation_notice_hours',
        'cancellation_fee_type',
        'cancellation_fee_value',
    ];

    private int $noticeHours;
    private string $feeType;
    private ?float $feeValue;

    public function __construct(int $noticeHours, string $feeType, ?float $feeValue = null)
    {
        $this->noticeHours = $noticeHours;
        $this->feeType = $feeType;
        $this->feeValue = CancellationFeeType::requiresValue($feeType) ? $feeValue : null;
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_NOTICE_HOURS, self::DEFAULT_FEE_TYPE);
    }

    /**
     * Resuelve la política heredando entre capas ordenadas por prioridad
     * (ej: atributos del evento, del schedule, de los settings). Para cada
     * campo gana la primera capa que lo tenga en no-null; si ninguna lo
     * tiene, cae al default genérico. El aviso hereda independiente del
     * cargo, pero tipo y valor del cargo se toman juntos de la misma capa.
     */
    public static function resolve(array ...$layers): self
    {
        $noticeHours = null;
        $feeType = null;
        $feeValue = null;

        foreach ($layers as $layer) {
            if ($noticeHours === null && isset($layer['cancellation_notice_hours'])) {
                $noticeHours = (int) $layer['cancellation_notice_hours'];
            }

            if ($feeType === null && isset($layer['cancellation_fee_type'])) {
                $feeType = $layer['cancellation_fee_type'];
                $feeValue = isset($layer['cancellation_fee_value']) ? (float) $layer['cancellation_fee_value'] : null;
            }
        }

        return new self(
            $noticeHours ?? self::DEFAULT_NOTICE_HOURS,
            $feeType !== null && CancellationFeeType::isValid($feeType) ? $feeType : self::DEFAULT_FEE_TYPE,
            $feeValue
        );
    }

    /**
     * Normaliza los campos de política de un request: si el tipo de cargo
     * pasa a uno sin valor asociado (o se limpia el override con null),
     * limpia también el valor viejo del registro.
     */
    public static function normalizeInput(array $data): array
    {
        if (array_key_exists('cancellation_fee_type', $data) && !CancellationFeeType::requiresValue($data['cancellation_fee_type'])) {
            $data['cancellation_fee_value'] = null;
        }

        return $data;
    }

    public function toArray(): array
    {
        return [
            'cancellation_notice_hours' => $this->noticeHours,
            'cancellation_fee_type' => $this->feeType,
            'cancellation_fee_value' => $this->feeValue,
        ];
    }

    /**
     * Texto para {{cancellation_notice}}: "24 horas", "1 hora".
     */
    public function noticeText(): string
    {
        return $this->noticeHours === 1 ? '1 hora' : "{$this->noticeHours} horas";
    }

    /**
     * Texto para {{cancellation_fee}}: "la sesión completa",
     * "el 50% del valor de la sesión", "$10".
     */
    public function feeText(): string
    {
        switch ($this->feeType) {
            case CancellationFeeType::PERCENTAGE:
                return 'el ' . $this->formatNumber($this->feeValue ?? 0) . '% del valor de la sesión';
            case CancellationFeeType::FIXED_AMOUNT:
                return '$' . $this->formatNumber($this->feeValue ?? 0);
            case CancellationFeeType::FULL_SESSION:
            default:
                return 'la sesión completa';
        }
    }

    /**
     * Formatea sin decimales de relleno: 50.00 → "50", 12.50 → "12.5".
     */
    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
