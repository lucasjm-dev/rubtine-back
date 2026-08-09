<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Users\Models\User;
use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskEventSchedule extends Model
{
    use SerializesDatesInAppTimezone;

    protected $table = 'task_event_schedules';

    protected $fillable = [
        'task_id',
        'user_id',
        'recurrence_type',
        'days_of_week',
        'day_of_month',
        'time_start',
        'time_end',
        'description',
        'starts_at',
        'ends_at',
        'horizon_generated_until',
        'active',
        'reminder_minutes_before',
        'cancellation_notice_hours',
        'cancellation_fee_type',
        'cancellation_fee_value',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'horizon_generated_until' => 'date',
        'active' => 'boolean',
        'cancellation_notice_hours' => 'integer',
        'cancellation_fee_value' => 'float',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TaskEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeNeedsExtension($query, $thresholdDate)
    {
        return $query->active()
            ->where('recurrence_type', '!=', \App\Domains\Tasks\Enums\ScheduleRecurrenceType::ONCE)
            ->where('horizon_generated_until', '<=', $thresholdDate)
            ->where(function ($q) use ($thresholdDate) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $thresholdDate);
            });
    }
}
