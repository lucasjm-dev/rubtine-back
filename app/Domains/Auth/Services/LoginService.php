<?php

namespace App\Domains\Auth\Services;

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class LoginService
{
    public function attemptLogin(array $data)
    {
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if (! Auth::attempt($credentials)) {
            return ApiResponse::error('auth_invalid_credentials', null, 401);
        }

        /** @var User */
        $user = Auth::user();

        if (! $user->canLoginAs($data['login_as'])) {
            return ApiResponse::error(
                'auth_invalid_login_as',
                null,
                403,
                ['reason' => 'The provided login_as type is not allowed.']
            );
        }

        $customClaims = ['login_as' => $data['login_as']];
        $token = Auth::claims($customClaims)->login($user);

        return ApiResponse::success([
            'token' => $token,
        ]);
    }
}
