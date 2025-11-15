<?php

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class SimpleUserService
{
    public function create($userData = null, array $simpleData)
    {
        if (!empty($simpleData['user_id'])) {
            $user = User::findOrFail($simpleData['user_id']);
            if (!empty($userData))
                $user->update($userData);
        } else {
            $user = User::create($userData);
        }

        $simpleUser = SimpleUser::firstOrCreate(
            ['user_id' => $user->id],
            $simpleData
        );
        // Always update
        $simpleUser->update($simpleData);

        $token = Auth::claims(['login_as' => User::TYPE_SIMPLE])->login($user);

        return ApiResponse::success([
            'token' => $token
        ]);
    }

    public function update($data = null, $simpleData)
    {
        /** @var User */
        $user = auth()->user();

        if (!empty($data)) {
            $user->update($data);
        }

        if (! $simpleUser = $user->simpleUser) {
            return ApiResponse::error("simple_user_not_found", null, 403);
        }
        $simpleUser->update($simpleData);

        return ApiResponse::success([
            'user' => $user->fresh(),
            'simple_user' => $simpleUser->fresh(),
        ]);
    }
}
