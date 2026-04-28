<?php

use App\Domains\Beneficiaries\Controllers\BeneficiaryController;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('beneficiaries')->group(function () {
    Route::middleware(['auth:api', User::TYPE_PROFESSIONAL])->group(function () {
        Route::get('/', [BeneficiaryController::class, 'index']);
        Route::post('/', [BeneficiaryController::class, 'create']);
        Route::put('/{beneficiary}', [BeneficiaryController::class, 'update'])->where('beneficiary', '[0-9]{1,19}');
    });
});
