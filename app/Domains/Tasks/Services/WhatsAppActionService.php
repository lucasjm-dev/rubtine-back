<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\TaskEvent;
use App\Helpers\ApiResponse;

class WhatsAppActionService
{
    private const ACTIONABLE_STATUSES = [
        TaskEventStatus::PENDING,
        TaskEventStatus::TO_CONFIRM
    ];

    public function confirm(string $token)
    {
        return $this->updateStatus($token, TaskEventStatus::CONFIRMED);
    }

    public function cancel(string $token)
    {
        return $this->updateStatus($token, TaskEventStatus::CANCELLED);
    }

    private function updateStatus(string $token, string $newStatus)
    {
        $event = TaskEvent::where('action_token', $token)->first();

        if (!$event) {
            return ApiResponse::error('El enlace no es válido o ha expirado.', null, 404);
        }

        if (!in_array($event->status, self::ACTIONABLE_STATUSES, true)) {
            return ApiResponse::error(
                'Esta tarea ya fue procesada anteriormente.',
                ['current_status' => $event->status],
                409
            );
        }

        $event->update(['status' => $newStatus]);

        $event->load('task');

        return ApiResponse::success([
            'event_id'   => $event->id,
            'task_title' => $event->task->title ?? null,
            'status'     => $event->status,
            'message'    => $newStatus === TaskEventStatus::COMPLETED
                ? '✅ La tarea fue marcada como completada.'
                : '❌ La tarea fue cancelada.',
        ]);
    }
}
