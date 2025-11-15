<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class ProfessionalUserService
{
    public function create($userData = null, array $professionalData)
    {
        if (!empty($professionalData['user_id'])) {
            $user = User::findOrFail($professionalData['user_id']);
        } else {
            $user = User::create($userData);
        }

        $professionalUser = ProfessionalUser::firstOrCreate(
            ['user_id' => $user->id],
            $professionalData
        );

        $token = Auth::claims(['login_as' => User::TYPE_PROFESSIONAL])->login($user);

        return [
            'token' => $token
        ];
    }

    public function update($data = null, $professionalData)
    {
        /** @var User */
        $user = auth()->user();

        if (!empty($data)) {
            $user->update($data);
        }

        if (! $professionalUser = $user->professionalUser) {
            return ApiResponse::error("professional_user_not_found", null, 403);
        }
        $professionalUser->update($professionalData);

        return ApiResponse::success([
            'user' => $user->fresh(),
            'professional_user' => $professionalUser->fresh(),
        ]);
    }
}
