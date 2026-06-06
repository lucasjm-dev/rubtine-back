<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Services\WhatsAppActionService;
use App\Http\Controllers\Controller;

class WhatsAppActionController extends Controller
{
    private WhatsAppActionService $service;

    public function __construct(WhatsAppActionService $service)
    {
        $this->service = $service;
    }

    public function confirm(string $token)
    {
        return $this->service->confirm($token);
    }

    public function cancel(string $token)
    {
        return $this->service->cancel($token);
    }
}
