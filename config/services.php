<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp' => [
        'api_url'              => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v21.0'),
        'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'access_token'         => env('WHATSAPP_ACCESS_TOKEN', ''),
        // Código de idioma exacto del template aprobado en Meta
        // ("Español (ECU)" → es_EC). Si Meta devuelve el error 132001
        // (template not found), revisar el código en Business Suite.
        'template_language'    => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_EC'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
    ],

];
