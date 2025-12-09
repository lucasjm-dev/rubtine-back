<?php

use App\Domains\Users\Controllers\CompanyUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('companyUser')->group(function () {

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/create', [CompanyUserController::class, 'create']);
    });

    Route::middleware(['auth:api', 'companyUser'])->group(function () {
        Route::post('/update', [CompanyUserController::class, 'update']);
        Route::delete('/delete', [CompanyUserController::class, 'delete']);
    });
});
