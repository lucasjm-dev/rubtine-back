<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Requests\Concerns\ValidatesCancellationPolicy;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskEventRequest extends FormRequest
{
    use ValidatesCancellationPolicy;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'description' => ['nullable', 'string', 'max:512'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', TaskEventStatus::values())],
            'scheduled_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:scheduled_at'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0'],
        ], $this->cancellationPolicyRules());
    }
}
