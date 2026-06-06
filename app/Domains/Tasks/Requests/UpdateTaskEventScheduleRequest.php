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
        $recurrenceType = $this->input('recurrence_type') ?? ($this->route('schedule') ? $this->route('schedule')->recurrence_type : null);
        $rules = [
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
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0'],
        ];

        if ($recurrenceType === ScheduleRecurrenceType::ONCE) {
            $rules['days_of_week'] = ['nullable', 'prohibited'];
            $rules['day_of_month'] = ['nullable', 'prohibited'];
            $rules['ends_at'] = ['nullable', 'prohibited'];
        } elseif ($recurrenceType === ScheduleRecurrenceType::WEEKLY) {
            $rules['days_of_week'] = ['sometimes', 'array', 'min:1'];
            $rules['day_of_month'] = ['nullable', 'prohibited'];
        } elseif ($recurrenceType === ScheduleRecurrenceType::MONTHLY) {
            $rules['day_of_month'] = ['sometimes', 'nullable', 'integer', 'between:1,31'];
            $rules['days_of_week'] = ['nullable', 'prohibited'];
        } elseif ($recurrenceType === ScheduleRecurrenceType::DAILY) {
            $rules['days_of_week'] = ['nullable', 'prohibited'];
            $rules['day_of_month'] = ['nullable', 'prohibited'];
        }
        return $rules;
    }
}
