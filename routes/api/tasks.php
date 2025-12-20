<?php

use App\Domains\Tasks\Controllers\TaskController;
use App\Domains\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('task')->group(function () {
    Route::middleware(['auth:api', 'simpleUser'])->group(function () {
        Route::post('/create', [TaskController::class, 'create']);
        Route::post('/update/{task}', [TaskController::class, 'update'])->where('task', '[0-9]{1,19}');
        Route::delete('/delete/{task}', [TaskController::class, 'delete'])->where('task', '[0-9]{1,19}');
    });
});
