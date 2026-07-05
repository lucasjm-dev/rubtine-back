<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\TaskEvent;
use Illuminate\Support\Facades\Log;

class WhatsAppActionService
{
    private const ACTIONABLE_STATUSES = [
        TaskEventStatus::PENDING,
        TaskEventStatus::TO_CONFIRM
    ];

    private WhatsAppNotificationService $notifications;

    public function __construct(WhatsAppNotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Procesa un mensaje entrante del webhook de WhatsApp.
     *
     * Los botones quick reply del template llegan como type "button" con el
     * payload configurado al enviar: "confirm:{token}" o "cancel:{token}".
     * Cualquier otro mensaje se ignora. Tras aplicar la acción se le responde
     * al paciente con un texto libre (estamos dentro de la ventana de 24h
     * porque él acaba de escribir).
     */
    public function handleIncomingMessage(array $message): void
    {
        if (($message['type'] ?? null) !== 'button') {
            return;
        }

        $payload = $message['button']['payload'] ?? '';

        if (!preg_match('/^(confirm|cancel):([A-Za-z0-9]{48})$/', $payload, $matches)) {
            Log::warning('WhatsApp webhook: unrecognized button payload', [
                'payload' => $payload,
            ]);

            return;
        }

        [, $action, $token] = $matches;

        $newStatus = $action === 'confirm'
            ? TaskEventStatus::CONFIRMED
            : TaskEventStatus::CANCELLED;

        $result = $this->updateStatus($token, $newStatus);

        Log::info('WhatsApp webhook: quick reply processed', [
            'action'   => $action,
            'success'  => $result['success'],
            'event_id' => $result['event_id'] ?? null,
        ]);

        if (!empty($message['from'])) {
            $this->notifications->sendTextMessage($message['from'], $result['message']);
        }
    }

    /**
     * @return array{success: bool, http_status: int, message: string, event_id?: int, status?: string}
     */
    private function updateStatus(string $token, string $newStatus): array
    {
        $event = TaskEvent::where('action_token', $token)->first();

        if (!$event) {
            return [
                'success'     => false,
                'http_status' => 404,
                'message'     => 'El enlace no es válido o ha expirado.',
            ];
        }

        if (!in_array($event->status, self::ACTIONABLE_STATUSES, true)) {
            return [
                'success'     => false,
                'http_status' => 409,
                'message'     => 'Este turno ya fue procesado anteriormente.',
                'event_id'    => $event->id,
                'status'      => $event->status,
            ];
        }

        $event->update(['status' => $newStatus]);

        return [
            'success'     => true,
            'http_status' => 200,
            'message'     => $newStatus === TaskEventStatus::CONFIRMED
                ? '✅ ¡Gracias! Tu asistencia quedó confirmada.'
                : '❌ Tu turno fue cancelado.',
            'event_id'    => $event->id,
            'status'      => $event->status,
        ];
    }
}
