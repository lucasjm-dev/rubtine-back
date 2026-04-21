<?php

use App\Domains\Tasks\Controllers\TaskParticipantController;
use Illuminate\Support\Facades\Route;

Route::prefix('task-participants')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/', [TaskParticipantController::class, 'index']);
        Route::post('/{task}', [TaskParticipantController::class, 'create'])->where('task', '[0-9]{1,19}');
        Route::post('/{task}/cancel', [TaskParticipantController::class, 'cancel'])->where('task', '[0-9]{1,19}');
        Route::post('/{taskParticipant}/accept', [TaskParticipantController::class, 'accept'])->where('taskParticipant', '[0-9]{1,19}');
        Route::post('/{taskParticipant}/reject', [TaskParticipantController::class, 'reject'])->where('taskParticipant', '[0-9]{1,19}');
    });
});
