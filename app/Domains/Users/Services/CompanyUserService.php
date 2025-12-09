<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CompanyUserService
{
    public function create(array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->companyUser()->exists()) {
            return ApiResponse::error(
                'company_user_already_exists',
                null,
                422
            );
        }

        $companyUser = $user->companyUser()->create($data);

        $token = Auth::claims([
            'login_as' => User::TYPE_COMPANY
        ])->login($user);

        return ApiResponse::success([
            'token' => $token,
            'login_as' => User::TYPE_COMPANY,
            'company_user' => $companyUser->fresh()
        ]);
    }



    public function update($data)
    {
        /** @var User */
        $user = auth()->user();

        if (! $companyUser = $user->companyUser) {
            return ApiResponse::error("company_user_not_found", null, 403);
        }
        $companyUser->update($data);

        return ApiResponse::success($companyUser->fresh());
    }
}
