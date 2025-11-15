<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\LoginUserRequest;
use App\Domains\Auth\Services\LoginService;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Traits\LoadsUserProfiles;

class AuthController extends Controller
{
    use LoadsUserProfiles;

    public function login(LoginUserRequest $request, LoginService $service)
    {
        $data = $request->validated();
        return $service->attemptLogin($data);
    }

    public function me()
    {
        $user = $this->withProfiles(auth()->user());
        return ApiResponse::success($user);
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
