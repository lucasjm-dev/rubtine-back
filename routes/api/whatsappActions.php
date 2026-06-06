<?php

use App\Domains\Tasks\Controllers\WhatsAppActionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp Action Routes (public — no auth required)
|--------------------------------------------------------------------------
|
| These endpoints are hit when a user taps a button in a WhatsApp
| notification message. Authentication is handled by the unique,
| unguessable action_token embedded in the URL.
|
*/

Route::prefix('whatsapp/actions/{token}')
    ->where(['token' => '[A-Za-z0-9]{48}'])
    ->group(function () {
        Route::post('/confirm', [WhatsAppActionController::class, 'confirm']);
        Route::post('/cancel', [WhatsAppActionController::class, 'cancel']);
    });
