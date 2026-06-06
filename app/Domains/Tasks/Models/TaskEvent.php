<?php

namespace App\Domains\Tasks\Models;

use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TaskEvent extends Model
{
    protected $table = 'task_events';

    protected $fillable = [
        'task_id',
        'user_id',
        'task_event_schedule_id',
        'description',
        'status',
        'scheduled_at',
        'ends_at',
        'is_manually_edited',
        'action_token',
    ];

    protected $hidden = [
        'action_token',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_manually_edited' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (TaskEvent $event) {
            if (empty($event->action_token)) {
                $event->action_token = Str::random(48);
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(EventNotification::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TaskEventSchedule::class, 'task_event_schedule_id');
    }

    public function scopeOwnedByUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('task.participants', function ($q) use ($user) {
            $q->where('task_participants.user_id', $user->id)
                ->where('task_participants.task_role', TaskParticipantTaskRole::OWNER);
        });
    }
}
