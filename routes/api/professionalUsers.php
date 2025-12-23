<?php

use App\Domains\Users\Controllers\ProfessionalUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('professionalUser')->group(function () {

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/create', [ProfessionalUserController::class, 'create']);
    });
    Route::middleware(['auth:api', 'professionalUser'])->group(function () {
        Route::post('/update', [ProfessionalUserController::class, 'update']);
        Route::delete('/delete', [ProfessionalUserController::class, 'delete']);
        Route::post('/request/{task}/create', [ProfessionalUserController::class, 'createRequest'])->where('task', '[0-9]{1,19}');
        Route::post('/request/{task}/cancel', [ProfessionalUserController::class, 'cancelRequest'])->where('task', '[0-9]{1,19}');
    });
});
