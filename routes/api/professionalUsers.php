<?php

use App\Domains\Users\Controllers\ProfessionalUserController;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('professionalUser')->group(function () {

    Route::post('/create', [ProfessionalUserController::class, 'create']);

    Route::middleware(['auth:api', 'userType:' . User::TYPE_PROFESSIONAL])->group(function () {
        Route::post('/update', [ProfessionalUserController::class, 'update']);
        Route::delete('/delete', [ProfessionalUserController::class, 'delete']);
    });
});
