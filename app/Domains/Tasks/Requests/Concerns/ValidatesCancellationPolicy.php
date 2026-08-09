<?php

namespace App\Domains\Tasks\Requests\Concerns;

use App\Domains\Tasks\Enums\CancellationFeeType;

/**
 * Reglas compartidas de los campos de política de cancelación.
 *
 * El valor solo se acepta (y se exige) cuando el tipo de cargo lo requiere;
 * para PERCENTAGE además se limita a 0..100.
 */
trait ValidatesCancellationPolicy
{
    protected function cancellationPolicyRules(): array
    {
        $typesWithValue = CancellationFeeType::PERCENTAGE . ',' . CancellationFeeType::FIXED_AMOUNT;

        $valueRules = [
            'nullable',
            'numeric',
            'min:0',
            'prohibited_unless:cancellation_fee_type,' . $typesWithValue,
            'required_if:cancellation_fee_type,' . $typesWithValue,
        ];

        $valueRules[] = $this->input('cancellation_fee_type') === CancellationFeeType::PERCENTAGE
            ? 'max:100'
            : 'max:99999999';

        return [
            'cancellation_notice_hours' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:720'],
            'cancellation_fee_type' => ['sometimes', 'nullable', 'string', 'in:' . implode(',', CancellationFeeType::values())],
            'cancellation_fee_value' => $valueRules,
        ];
    }
}
