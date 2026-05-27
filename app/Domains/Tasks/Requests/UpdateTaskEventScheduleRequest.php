<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\ScheduleRecurrenceType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskEventScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recurrence_type' => ['sometimes', 'string', 'in:' . implode(',', ScheduleRecurrenceType::values())],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'time_start' => ['sometimes', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:512'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
