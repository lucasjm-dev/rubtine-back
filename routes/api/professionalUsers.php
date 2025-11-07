<?php

use App\Http\Controllers\Api\V1\ProfessionalUserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('professionalUser')->group(function () {

    Route::post('/create', [ProfessionalUserController::class, 'create']);

    Route::middleware(['auth:api', 'userType:' . User::TYPE_PROFESSIONAL])->group(function () {
        Route::post('/update', [ProfessionalUserController::class, 'update']);
        Route::delete('/delete', [ProfessionalUserController::class, 'delete']);
    });
});
