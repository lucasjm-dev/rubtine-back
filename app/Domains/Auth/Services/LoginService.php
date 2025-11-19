<?php

namespace App\Domains\Auth\Services;

use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Traits\LoadsUserProfiles;
use Illuminate\Support\Facades\Auth;

class LoginService
{
    use LoadsUserProfiles;

    public function register($userData = null)
    {
        $user = User::create($userData);

        return ApiResponse::success([
            'token' => Auth::login($user)
        ]);
    }

    public function attemptLogin(array $data)
    {
        if (! Auth::attempt($data)) {
            return ApiResponse::error('auth_invalid_credentials', null, 401);
        }

        /** @var User $user */
        $user = $this->loadProfiles(Auth::user());
        $loginAs = $user->autoDetectLoginAs();
        $claims = [];

        if ($loginAs !== null) {
            $claims['login_as'] = $loginAs;
        }

        $token = Auth::claims($claims)->login($user);

        return ApiResponse::success([
            'token' => $token,
            'login_as' => $loginAs,
            'user' => $user
        ]);
    }

    public function switchLoginAs(string $loginAs)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! in_array($loginAs, $user->getAvailableTypes(), true)) {
            return ApiResponse::error('auth_invalid_login_as', [
                'reason' => "User does not have the profile '$loginAs'"
            ], 403);
        }

        $user = $this->loadProfiles($user);


        $token = Auth::claims([
            'login_as' => $loginAs
        ])->login($user);

        return ApiResponse::success([
            'token' => $token,
            'login_as' => $loginAs,
        ]);
    }
}
