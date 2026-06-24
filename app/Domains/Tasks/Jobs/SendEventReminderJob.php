<?php

namespace App\Domains\Tasks\Jobs;

use App\Domains\Tasks\Models\TaskEvent;
use App\Domains\Tasks\Services\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    private int $taskEventId;

    public function __construct(int $taskEventId)
    {
        $this->taskEventId = $taskEventId;
    }

    public function handle(WhatsAppNotificationService $whatsapp): void
    {
        $event = TaskEvent::with('task.beneficiary')->find($this->taskEventId);

        if ($event === null) {
            return;
        }

        $task = $event->task;
        $beneficiary = $task ? $task->beneficiary : null;

        if ($beneficiary === null || empty($beneficiary->phone)) {
            return;
        }

        $name = trim("{$beneficiary->name} {$beneficiary->last_name}");

        $whatsapp->sendEventReminder($event, $beneficiary->phone, $name);
    }
}
