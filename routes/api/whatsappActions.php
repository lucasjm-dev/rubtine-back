<?php

use App\Domains\Tasks\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp Routes (public — no auth required)
|--------------------------------------------------------------------------
|
| Webhook: Meta verifies the callback URL with GET and delivers incoming
| messages (including template quick reply button taps) with POST. The GET
| is authenticated by the verify token; button actions are authenticated
| by the unguessable action_token inside the button payload.
|
| Action routes: legacy URL-button endpoints, kept as a manual fallback.
|
*/

Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);
