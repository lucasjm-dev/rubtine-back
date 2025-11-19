<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class SimpleUserService
{
    public function create(array $simpleData)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->simpleUser()->exists()) {
            return ApiResponse::error(
                'simple_user_already_exists',
                null,
                422
            );
        }

        $simpleUser = $user->simpleUser()->create($simpleData);

        $token = Auth::claims([
            'login_as' => User::TYPE_SIMPLE
        ])->login($user);

        return ApiResponse::success([
            'token' => $token,
            'simple_user' => $simpleUser->fresh(),
        ]);
    }



    public function update($simpleData)
    {
        /** @var User */
        $user = auth()->user();

        if (! $simpleUser = $user->simpleUser) {
            return ApiResponse::error("simple_user_not_found", null, 403);
        }
        $simpleUser->update($simpleData);

        return ApiResponse::success($simpleUser->fresh());
    }
}
