<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Services\WhatsAppActionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de la API de WhatsApp Business Cloud (Meta).
 *
 * Se configura en la app de Meta (WhatsApp → Configuration → Webhook):
 *   Callback URL: https://api.rubtine.com.ar/api/v1/whatsapp/webhook
 *   Verify token: el valor de WHATSAPP_WEBHOOK_VERIFY_TOKEN
 *   Campo suscripto: messages
 *
 * Meta llama con GET para verificar la URL y con POST para entregar los
 * eventos (mensajes entrantes, incluidos los botones quick reply).
 */
class WhatsAppWebhookController extends Controller
{
    private WhatsAppActionService $service;

    public function __construct(WhatsAppActionService $service)
    {
        $this->service = $service;
    }

    public function verify(Request $request)
    {
        // PHP convierte los puntos de hub.mode / hub.verify_token / hub.challenge
        // en guiones bajos al parsear la query string.
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $messages = $change['value']['messages'] ?? [];

                foreach ($messages as $message) {
                    try {
                        $this->service->handleIncomingMessage($message);
                    } catch (\Throwable $e) {
                        Log::error('WhatsApp webhook: error processing message', [
                            'error'   => $e->getMessage(),
                            'message' => $message,
                        ]);
                    }
                }
            }
        }

        // Siempre 200: si Meta no recibe 200 reintenta y puede terminar
        // deshabilitando el webhook.
        return response()->json(['status' => 'ok']);
    }
}
