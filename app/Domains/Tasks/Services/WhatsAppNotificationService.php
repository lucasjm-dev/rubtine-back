<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\EventNotificationStatus;
use App\Domains\Tasks\Enums\EventNotificationType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Models\TaskEvent;
use App\Domains\Tasks\Support\CancellationPolicy;
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
 *     WHATSAPP_TEMPLATE_LANGUAGE=es_EC
 *     WHATSAPP_WEBHOOK_VERIFY_TOKEN=un_token_secreto
 *
 * ── Templates en Meta Business Suite ────────────────────────
 *  Hay dos templates de recordatorio; más adelante se elegirá cuál usar
 *  según la preferencia del usuario. Por ahora se envía SIEMPRE
 *  task_event_reminder_policy.
 *
 *  1) task_event_reminder
 *     Categoría: UTILITY
 *     Idioma: Español (ECU) → código es_EC
 *     Variables: con nombre (patient_name, professional_name, event_date, event_hour)
 *     Body:
 *         📅 ¡Hola, {{patient_name}}! Te recordamos tu turno con
 *         {{professional_name}} el día *{{event_date}} a las {{event_hour}}*.
 *         ¿Confirmás tu asistencia?
 *
 *  2) task_event_reminder_policy (en uso actualmente)
 *     Categoría: UTILITY
 *     Idioma: Español (ECU) → código es_EC
 *     Variables: con nombre (patient_name, professional_name, event_date,
 *     event_hour, cancellation_notice, cancellation_fee)
 *     Body:
 *         📅 ¡Hola, {{patient_name}}! Te recordamos tu turno con
 *         {{professional_name}} el día {{event_date}} a las {{event_hour}}.
 *
 *         Las cancelaciones deben avisarse con {{cancellation_notice}} de
 *         anticipación; de lo contrario, se abona {{cancellation_fee}}.
 *
 *         ¿Confirmás tu asistencia?
 *
 *  Buttons (tipo: quick reply, en ambos):
 *      1. Confirmar
 *      2. Cancelar
 *
 *  ⚠ Los botones quick reply NO abren URLs: cuando el paciente toca uno,
 *    Meta envía el payload del botón al webhook configurado en la app
 *    (GET/POST /api/v1/whatsapp/webhook). El payload lleva la acción y el
 *    action_token del evento: "confirm:{token}" o "cancel:{token}".
 * ─────────────────────────────────────────────────────────────
 */
class WhatsAppNotificationService
{
    private string $apiUrl;
    private string $phoneNumberId;
    private string $accessToken;
    private string $templateLanguage;

    public function __construct()
    {
        $this->apiUrl           = config('services.whatsapp.api_url', 'https://graph.facebook.com/v21.0');
        $this->phoneNumberId    = config('services.whatsapp.phone_number_id', '');
        $this->accessToken      = config('services.whatsapp.access_token', '');
        $this->templateLanguage = config('services.whatsapp.template_language', 'es_EC');
    }

    public function sendEventReminder(TaskEvent $event, string $phone, string $patientName): bool
    {
        $event->loadMissing('task');

        $professional = $event->task
            ? $event->task->acceptedProfessionalParticipants()->with('user.professionalUser')->first()
            : null;

        $professionalName = $this->resolveProfessionalName($professional);

        $eventDate = $event->scheduled_at->format('d/m/Y');
        $eventHour = $event->scheduled_at->format('H:i');

        // En local manda el template de prueba hello_world (pre-aprobado por
        // Meta, sin variables ni botones y sin ventana de 24h). En cualquier
        // otro ambiente usa el template real del recordatorio.
        $payload = app()->environment('local')
            ? $this->buildTestTemplatePayload($phone)
            : $this->buildTemplatePayload(
                $event,
                $phone,
                $patientName,
                $professionalName,
                $eventDate,
                $eventHour,
                $this->resolveCancellationPolicy($event, $professional)
            );

        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->withOptions(['connect_timeout' => 10])
                ->post($url, $payload);

            if ($response->successful()) {
                $waMessageId = $response->json('messages.0.id');

                Log::info('WhatsApp notification sent', [
                    'task_event_id' => $event->id,
                    'phone'         => $phone,
                    'wa_message_id' => $waMessageId,
                ]);

                $this->recordNotification($event, EventNotificationStatus::SENT, $waMessageId);
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


    private function resolveCancellationPolicy(TaskEvent $event, $participant): CancellationPolicy
    {
        $event->loadMissing('schedule');

        $user = $participant ? $participant->user : null;
        $professionalUser = $user ? $user->professionalUser : null;
        $settings = $professionalUser ? $professionalUser->settings : null;

        return CancellationPolicy::resolve(
            $event->only(CancellationPolicy::FIELDS),
            $event->schedule ? $event->schedule->only(CancellationPolicy::FIELDS) : [],
            $settings ? $settings->only(CancellationPolicy::FIELDS) : []
        );
    }

    private function resolveProfessionalName($participant): string
    {
        $user = $participant ? $participant->user : null;
        $profile = $user ? $user->professionalUser : null;

        $title = $profile ? $profile->genderedTitle() : null;
        $fullName = $user ? $user->full_name : null;

        if ($fullName) {
            return $title ? "{$title} {$fullName}" : $fullName;
        }

        return $title ?: 'el profesional';
    }

    /**
     * Marca el resultado del envío en la notificación.
     *
     * Si ya existe una notificación WhatsApp en estado PENDING (la "reclamada"
     * por el comando events:send-reminders), la actualiza. Si no existe ninguna
     * (ej. al llamar a este servicio directamente), crea una nueva.
     */
    private function recordNotification(TaskEvent $event, string $status, ?string $waMessageId = null): void
    {
        // Nunca propagar errores de esta contabilidad: para cuando se llama,
        // el mensaje ya salió (o ya falló) en Meta, y una excepción acá haría
        // que la cola reintente el job y le duplique el WhatsApp al paciente.
        try {
            $sentAt = $status === EventNotificationStatus::SENT ? now() : null;

            $notification = $event->notifications()
                ->where('type', EventNotificationType::WHATSAPP)
                ->where('status', EventNotificationStatus::PENDING)
                ->latest('id')
                ->first();

            if ($notification !== null) {
                $notification->update([
                    'status'        => $status,
                    'sent_at'       => $sentAt,
                    'wa_message_id' => $waMessageId,
                ]);

                return;
            }

            $event->notifications()->create([
                'type'          => EventNotificationType::WHATSAPP,
                'status'        => $status,
                'send_at'       => now(),
                'sent_at'       => $sentAt,
                'wa_message_id' => $waMessageId,
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsApp notification bookkeeping failed', [
                'task_event_id' => $event->id,
                'status'        => $status,
                'error'         => $e->getMessage(),
            ]);
        }
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
     * Payload con Message Template aprobado ("task_event_reminder_policy").
     * El template usa variables con nombre: {{patient_name}},
     * {{professional_name}}, {{event_date}}, {{event_hour}},
     * {{cancellation_notice}} y {{cancellation_fee}}.
     * Los botones quick reply llevan un payload ("confirm:{token}" /
     * "cancel:{token}") que Meta devuelve al webhook cuando el paciente
     * toca el botón.
     */
    private function buildTemplatePayload(TaskEvent $event, string $phone, string $patientName, string $professionalName, string $eventDate, string $eventHour, CancellationPolicy $policy): array
    {
        $token = $event->action_token;

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'     => 'task_event_reminder_policy',
                'language' => ['code' => $this->templateLanguage],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'parameter_name' => 'patient_name', 'text' => $patientName],
                            ['type' => 'text', 'parameter_name' => 'professional_name', 'text' => $professionalName],
                            ['type' => 'text', 'parameter_name' => 'event_date', 'text' => $eventDate],
                            ['type' => 'text', 'parameter_name' => 'event_hour', 'text' => $eventHour],
                            ['type' => 'text', 'parameter_name' => 'cancellation_notice', 'text' => $policy->noticeText()],
                            ['type' => 'text', 'parameter_name' => 'cancellation_fee', 'text' => $policy->feeText()],
                        ],
                    ],
                    // Botón 0: "Confirmar"
                    [
                        'type'       => 'button',
                        'sub_type'   => 'quick_reply',
                        'index'      => '0',
                        'parameters' => [
                            ['type' => 'payload', 'payload' => "confirm:{$token}"],
                        ],
                    ],
                    // Botón 1: "Cancelar"
                    [
                        'type'       => 'button',
                        'sub_type'   => 'quick_reply',
                        'index'      => '1',
                        'parameters' => [
                            ['type' => 'payload', 'payload' => "cancel:{$token}"],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Mensaje de texto libre (fuera de template). Solo se puede enviar dentro
     * de la ventana de 24h posterior al último mensaje del usuario; se usa
     * para responder cuando el paciente toca un botón quick reply.
     */
    public function sendTextMessage(string $phone, string $text): bool
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(15)
                ->withOptions(['connect_timeout' => 10])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to'                => $phone,
                    'type'              => 'text',
                    'text'              => [
                        'body'        => $text,
                        'preview_url' => false,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('WhatsApp text message failed', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp text message exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
