<?php

use App\Domains\Categories\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->middleware('auth:api')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}/subcategories', [CategoryController::class, 'subcategories']);
});
