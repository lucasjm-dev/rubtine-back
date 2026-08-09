<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Tasks\Support\CancellationPolicy;
use App\Domains\Users\Requests\UpdateProfessionalUserSettingRequest;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

/**
 * Configuración general del profesional (professional_user_settings).
 *
 * GET devuelve los defaults que el frontend prefillea al crear eventos
 * (si no existen todavía, se crean con el default genérico).
 */
class ProfessionalUserSettingController extends Controller
{
    public function show()
    {
        $settings = auth()->user()->professionalUser->getOrCreateSettings();

        return ApiResponse::success($settings);
    }

    public function update(UpdateProfessionalUserSettingRequest $request)
    {
        $settings = auth()->user()->professionalUser->getOrCreateSettings();

        $settings->update(CancellationPolicy::normalizeInput($request->validated()));

        return ApiResponse::success($settings->fresh());
    }
}
