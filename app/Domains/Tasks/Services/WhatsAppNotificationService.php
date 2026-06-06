<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\EventNotificationStatus;
use App\Domains\Tasks\Enums\EventNotificationType;
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
        $token       = $event->action_token;

        /*
        |--------------------------------------------------------------
        | Payload para la API de WhatsApp Business Cloud
        |--------------------------------------------------------------
        |
        | Usa un Message Template aprobado ("task_event_reminder").
        | Los componentes del body referencian variables {{1}}, {{2}}, {{3}}.
        | Los botones tipo URL usan un sufijo dinámico ({{1}}) que se
        | concatena a la URL base configurada en el template.
        |
        */
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'     => 'task_event_reminder',
                'language' => ['code' => 'es'],
                'components' => [
                    // Body parameters: {{1}}=nombre, {{2}}=tarea, {{3}}=fecha
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

        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent', [
                    'task_event_id' => $event->id,
                    'phone'         => $phone,
                    'wa_message_id' => $response->json('messages.0.id'),
                ]);

                // Registrar la notificación enviada
                $event->notifications()->create([
                    'type'    => EventNotificationType::WHATSAPP,
                    'status'  => EventNotificationStatus::SENT,
                    'send_at' => now(),
                    'sent_at' => now(),
                ]);

                return true;
            }

            Log::error('WhatsApp notification failed', [
                'task_event_id' => $event->id,
                'status'        => $response->status(),
                'body'          => $response->json(),
            ]);

            $event->notifications()->create([
                'type'    => EventNotificationType::WHATSAPP,
                'status'  => EventNotificationStatus::FAILED,
                'send_at' => now(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification exception', [
                'task_event_id' => $event->id,
                'error'         => $e->getMessage(),
            ]);

            $event->notifications()->create([
                'type'    => EventNotificationType::WHATSAPP,
                'status'  => EventNotificationStatus::FAILED,
                'send_at' => now(),
            ]);

            return false;
        }
    }
}
