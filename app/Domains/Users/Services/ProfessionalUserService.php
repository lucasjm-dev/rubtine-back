<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\User;
use App\Domains\Users\Resources\ProfessionalUserResource;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class ProfessionalUserService
{
    public function create(array $professionalData)
    {
        /** @var User */
        $user = auth()->user();

        if ($user->professionalUser()->exists()) {
            return ApiResponse::error(
                'professional_user_already_exists',
                null,
                422
            );
        }

        $professionalUser = $user->professionalUser()->create($professionalData);

        $token = Auth::claims([
            'login_as' => User::TYPE_PROFESSIONAL
        ])->login($user);

        return ApiResponse::success([
            'token' => $token,
            'login_as' => User::TYPE_PROFESSIONAL,
            'professional_user' => $professionalUser->fresh()
        ]);
    }

    public function update($professionalData)
    {
        /** @var User */
        $user = auth()->user();

        if (! $professionalUser = $user->professionalUser) {
            return ApiResponse::error("professional_user_not_found", null, 403);
        }
        $professionalUser->update($professionalData);

        return ApiResponse::success(
            new ProfessionalUserResource(
                $professionalUser->fresh()
            )
        );
    }
}
