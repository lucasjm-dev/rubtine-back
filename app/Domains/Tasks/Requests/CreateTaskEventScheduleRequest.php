<?php

namespace App\Domains\Tasks\Requests;

use App\Domains\Tasks\Enums\ScheduleRecurrenceType;
use Illuminate\Foundation\Http\FormRequest;

class CreateTaskEventScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recurrence_type' => ['required', 'string', 'in:' . implode(',', ScheduleRecurrenceType::values())],
            'days_of_week' => ['nullable', 'array', 'required_if:recurrence_type,WEEKLY', 'prohibited_if:recurrence_type,ONCE,DAILY,MONTHLY'],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31', 'required_if:recurrence_type,MONTHLY', 'prohibited_if:recurrence_type,ONCE,DAILY,WEEKLY'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i', 'after:time_start'],
            'description' => ['nullable', 'string', 'max:512'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at', 'prohibited_if:recurrence_type,ONCE'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
