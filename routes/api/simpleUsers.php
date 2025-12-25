<?php

use App\Domains\Users\Controllers\SimpleUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('simple-user')->group(function () {

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/create', [SimpleUserController::class, 'create']);
    });

    Route::middleware(['auth:api', 'simpleUser'])->group(function () {
        Route::post('/update', [SimpleUserController::class, 'update']);
        Route::delete('/delete', [SimpleUserController::class, 'delete']);
    });
});
