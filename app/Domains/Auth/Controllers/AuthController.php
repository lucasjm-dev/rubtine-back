<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\LoginUserRequest;
use App\Domains\Auth\Requests\RegisterUserRequest;
use App\Domains\Auth\Requests\SwitchLoginUserRequest;
use App\Domains\Auth\Services\LoginService;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Traits\LoadsUserProfiles;

class AuthController extends Controller
{
    use LoadsUserProfiles;

    public function register(RegisterUserRequest $userRequest, LoginService $service)
    {
        $userData = $userRequest->validated();
        return $service->register($userData);
    }

    public function login(LoginUserRequest $request, LoginService $service)
    {
        $data = $request->validated();
        return $service->attemptLogin($data);
    }

    public function switchLogin(SwitchLoginUserRequest $request, LoginService $service)
    {
        $data = $request->validated();
        return $service->switchLoginAs($data['login_as']);
    }


    public function me()
    {
        $user = $this->loadProfiles(auth()->user());
        $loginAs = auth()->payload()->get('login_as', null);

        return ApiResponse::success([
            'user' => $user,
            'login_as' => $loginAs,
        ]);
    }


    public function logout()
    {
        return ApiResponse::success(auth()->logout());
    }

    public function refresh()
    {
        /** @var User */
        $user = auth();

        return $this->respondWithToken($user->refresh());
    }
}
