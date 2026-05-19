<?php

namespace App\Domains\Tasks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNotification extends Model
{
    protected $table = 'event_notifications';

    protected $fillable = [
        'task_event_id',
        'type',
        'status',
        'send_at',
        'sent_at',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function taskEvent(): BelongsTo
    {
        return $this->belongsTo(TaskEvent::class);
    }
}
