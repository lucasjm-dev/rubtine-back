<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function login(LoginUserRequest $request)
    {
        $data = $request->validated();
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
        if (! auth()->attempt($credentials)) {
            return ApiResponse::error('invalid_credentials', null, 401);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        if (! $user->canLoginAs($data['login_as'])) {
            return ApiResponse::error('login_as_no_valid', null, 403);
        }

        $customClaims = ['login_as' => $data['login_as']];
        $token = auth()->claims($customClaims)->login($user);
        return $this->respondWithToken($token);
    }


    public function me()
    {
        return ApiResponse::success(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return ApiResponse::success();
    }

    public function refresh()
    {
        /** @var User */
        $user = auth();

        return $this->respondWithToken($user->refresh());
    }
}
