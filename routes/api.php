<?php

use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/simpleUsers.php';
    require __DIR__ . '/api/professionalUsers.php';
    require __DIR__ . '/api/companyUsers.php';
});
