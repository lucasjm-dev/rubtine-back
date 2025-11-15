<?php

use App\Domains\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::post('/update', [UserController::class, 'update']);
        Route::delete('/delete', [UserController::class, 'delete']);
    });
});
