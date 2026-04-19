<?php

use App\Domains\Tasks\Controllers\TaskRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('task-requests')->group(function () {

    Route::middleware(['auth:api', 'professionalUser'])->group(function () {
        Route::post('/{task}', [TaskRequestController::class, 'create'])->where('task', '[0-9]{1,19}');
        Route::post('/{task}/cancel', [TaskRequestController::class, 'cancel'])->where('task', '[0-9]{1,19}');
    });

    Route::middleware(['auth:api', 'simpleUser'])->group(function () {
        Route::post('/{taskRequest}/accept', [TaskRequestController::class, 'accept'])->where('taskRequest', '[0-9]{1,19}');
        Route::post('/{taskRequest}/reject', [TaskRequestController::class, 'reject'])->where('taskRequest', '[0-9]{1,19}');
    });

    Route::get('/', [TaskRequestController::class, 'index']);
});
