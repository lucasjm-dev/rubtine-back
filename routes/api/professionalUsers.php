<?php

use App\Domains\Users\Controllers\ProfessionalUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('professional-user')->group(function () {

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/create', [ProfessionalUserController::class, 'create']);
    });
    Route::middleware(['auth:api', 'professionalUser'])->group(function () {
        Route::post('/update', [ProfessionalUserController::class, 'update']);
        Route::delete('/delete', [ProfessionalUserController::class, 'delete']);
    });
});
