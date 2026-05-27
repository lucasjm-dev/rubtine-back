<?php

use App\Domains\Tasks\Controllers\TaskEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks/{task}/event-schedules')->where(['task' => '[0-9]{1,19}', 'schedule' => '[0-9]{1,19}'])->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/', [\App\Domains\Tasks\Controllers\TaskEventScheduleController::class, 'index']);
        Route::post('/', [\App\Domains\Tasks\Controllers\TaskEventScheduleController::class, 'create']);
        Route::get('/{schedule}', [\App\Domains\Tasks\Controllers\TaskEventScheduleController::class, 'show']);
        Route::put('/{schedule}', [\App\Domains\Tasks\Controllers\TaskEventScheduleController::class, 'update']);
        Route::delete('/{schedule}', [\App\Domains\Tasks\Controllers\TaskEventScheduleController::class, 'delete']);
    });
});
