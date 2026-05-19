<?php

use App\Domains\Tasks\Controllers\TaskEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks/{task}/events')->where(['task' => '[0-9]{1,19}', 'event' => '[0-9]{1,19}'])->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/', [TaskEventController::class, 'index']);
        Route::post('/', [TaskEventController::class, 'create']);
        Route::get('/{event}', [TaskEventController::class, 'show']);
        Route::put('/{event}', [TaskEventController::class, 'update']);
        Route::delete('/{event}', [TaskEventController::class, 'delete']);
    });
});
