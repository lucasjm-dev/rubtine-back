<?php

use App\Domains\Users\Controllers\SimpleUserController;
use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('simpleUser')->group(function () {

    Route::post('/create', [SimpleUserController::class, 'create']);

    Route::middleware(['auth:api', 'userType:' . User::TYPE_SIMPLE])->group(function () {
        Route::post('/update', [SimpleUserController::class, 'update']);
        Route::delete('/delete', [SimpleUserController::class, 'delete']);
    });
});
