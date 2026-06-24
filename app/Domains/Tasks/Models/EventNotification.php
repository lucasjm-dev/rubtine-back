<?php

namespace App\Domains\Tasks\Models;

use App\Traits\SerializesDatesInAppTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNotification extends Model
{
    use SerializesDatesInAppTimezone;

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
