<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\EventNotificationStatus;
use App\Domains\Tasks\Enums\EventNotificationType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\TaskEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar notificaciones de tareas por WhatsApp
 * usando la API de WhatsApp Business Cloud (Meta).
 *
 * ── Requisitos previos ──────────────────────────────────────
 *  1. Crear una app en https://developers.facebook.com
 *  2. Activar el producto "WhatsApp" en la app
 *  3. Obtener el Phone Number ID y un Access Token permanente
 *  4. Crear un Message Template en Meta Business Suite
 *     (ver ejemplo de template más abajo)
 *  5. Configurar las variables en .env:
 *
 *     WHATSAPP_API_URL=https://graph.facebook.com/v21.0
 *     WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id
 *     WHATSAPP_ACCESS_TOKEN=tu_access_token_permanente
 *     WHATSAPP_ACTION_BASE_URL=https://tu-dominio.com/api/v1/whatsapp/actions
 *
 * ── Template de ejemplo en Meta Business Suite ──────────────
 *  Nombre: task_event_reminder
 *  Categoría: UTILITY
 *  Idioma: es
 *  Header: ninguno
 *  Body:
 *      Hola {{1}}, tenés una tarea pendiente:
 *      📋 *{{2}}*
 *      📅 {{3}}
 *      ¿Querés marcarla como completada o cancelarla?
 *  Footer: Rubtine
 *  Buttons (tipo: URL, con variable):
 *      1. ✅ Completar  → URL: {{1}}/complete    (tipo: URL dinámica)
 *      2. ❌ Cancelar   → URL: {{1}}/cancel       (tipo: URL dinámica)
 *
 *  ⚠ Los botones tipo URL en templates de WhatsApp Business abren
 *    el navegador del usuario. El sufijo dinámico se concatena
 *    a una URL base que configurás en el template.
 *    URL base del template: https://tu-dominio.com/api/v1/whatsapp/actions/
 *    Sufijo variable ({{1}}): {action_token}/complete  o  {action_token}/cancel
 * ─────────────────────────────────────────────────────────────
 */
class WhatsAppNotificationService
{
    private string $apiUrl;
    private string $phoneNumberId;
    private string $accessToken;
    private string $actionBaseUrl;

    public function __construct()
    {
        $this->apiUrl        = config('services.whatsapp.api_url', 'https://graph.facebook.com/v21.0');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->accessToken   = config('services.whatsapp.access_token', '');
        $this->actionBaseUrl = config('services.whatsapp.action_base_url', '');
    }

    public function sendEventReminder(TaskEvent $event, string $phone, string $userName): bool
    {
        $event->loadMissing('task');

        $taskTitle   = $event->task->title ?? 'Tarea sin título';
        $scheduledAt = $event->scheduled_at->format('d/m/Y H:i');

        // En local manda el template de prueba hello_world (pre-aprobado por
        // Meta, sin variables ni botones y sin ventana de 24h). En cualquier
        // otro ambiente usa el template real del recordatorio.
        $payload = app()->environment('local')
            ? $this->buildTestTemplatePayload($phone)
            : $this->buildTemplatePayload($event, $phone, $userName, $taskTitle, $scheduledAt);

        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->withOptions(['connect_timeout' => 10])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent', [
                    'task_event_id' => $event->id,
                    'phone'         => $phone,
                    'wa_message_id' => $response->json('messages.0.id'),
                ]);

                $this->recordNotification($event, EventNotificationStatus::SENT);
                $event->status = TaskEventStatus::TO_CONFIRM;
                $event->save();

                return true;
            }

            Log::error('WhatsApp notification failed', [
                'task_event_id' => $event->id,
                'status'        => $response->status(),
                'body'          => $response->json(),
            ]);

            $this->recordNotification($event, EventNotificationStatus::FAILED);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification exception', [
                'task_event_id' => $event->id,
                'error'         => $e->getMessage(),
            ]);

            $this->recordNotification($event, EventNotificationStatus::FAILED);

            return false;
        }
    }

    /**
     * Marca el resultado del envío en la notificación.
     *
     * Si ya existe una notificación WhatsApp en estado PENDING (la "reclamada"
     * por el comando events:send-reminders), la actualiza. Si no existe ninguna
     * (ej. al llamar a este servicio directamente), crea una nueva.
     */
    private function recordNotification(TaskEvent $event, string $status): void
    {
        $sentAt = $status === EventNotificationStatus::SENT ? now() : null;

        $notification = $event->notifications()
            ->where('type', EventNotificationType::WHATSAPP)
            ->where('status', EventNotificationStatus::PENDING)
            ->latest('id')
            ->first();

        if ($notification !== null) {
            $notification->update([
                'status'  => $status,
                'sent_at' => $sentAt,
            ]);

            return;
        }

        $event->notifications()->create([
            'type'    => EventNotificationType::WHATSAPP,
            'status'  => $status,
            'send_at' => now(),
            'sent_at' => $sentAt,
        ]);
    }

    /**
     * Template de prueba pre-aprobado por Meta (hello_world).
     *
     * No lleva variables ni botones y, al ser template, no requiere la ventana
     * de 24h: sirve para validar credenciales y entrega en local. El texto del
     * mensaje es fijo ("Hello World"), no se puede personalizar.
     */
    private function buildTestTemplatePayload(string $phone): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'     => 'hello_world',
                'language' => ['code' => 'en_US'],
            ],
        ];
    }

    /**
     * Payload con Message Template aprobado ("task_event_reminder").
     * Los componentes del body referencian {{1}}=nombre, {{2}}=tarea, {{3}}=fecha.
     * Los botones tipo URL usan un sufijo dinámico concatenado a la URL base
     * configurada en el template.
     */
    private function buildTemplatePayload(TaskEvent $event, string $phone, string $userName, string $taskTitle, string $scheduledAt): array
    {
        $token = $event->action_token;

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'     => 'task_event_reminder',
                'language' => ['code' => 'es'],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $userName],
                            ['type' => 'text', 'text' => $taskTitle],
                            ['type' => 'text', 'text' => $scheduledAt],
                        ],
                    ],
                    // Botón 0: "Completar" → URL base + {token}/complete
                    [
                        'type'       => 'button',
                        'sub_type'   => 'url',
                        'index'      => '0',
                        'parameters' => [
                            ['type' => 'text', 'text' => "{$token}/complete"],
                        ],
                    ],
                    // Botón 1: "Cancelar" → URL base + {token}/cancel
                    [
                        'type'       => 'button',
                        'sub_type'   => 'url',
                        'index'      => '1',
                        'parameters' => [
                            ['type' => 'text', 'text' => "{$token}/cancel"],
                        ],
                    ],
                ],
            ],
        ];
    }
}
