<?php

use App\Domains\Tasks\Controllers\ProfessionalTaskController;
use App\Domains\Tasks\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/mine', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'create']);
        Route::put('/{task}', [TaskController::class, 'update'])->where('task', '[0-9]{1,19}');
        Route::delete('/{task}', [TaskController::class, 'delete'])->where('task', '[0-9]{1,19}');
    });

    Route::middleware(['auth:api', 'professionalUser'])->group(function () {
        Route::get('/', [ProfessionalTaskController::class, 'index']);
        Route::get('/assigned', [ProfessionalTaskController::class, 'assigned']);
    });
});
