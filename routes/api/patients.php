<?php

use App\Domains\Patients\Controllers\PatientController;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('patients')->group(function () {
    Route::middleware(['auth:api', User::TYPE_PROFESSIONAL])->group(function () {
        Route::get('/', [PatientController::class, 'index']);
        Route::post('/', [PatientController::class, 'create']);
        Route::put('/{patient}', [PatientController::class, 'update'])->where('patient', '[0-9]{1,19}');
    });
});
