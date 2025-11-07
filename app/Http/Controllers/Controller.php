<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function respondWithToken($token)
    {
        /** @var User */
        $user = auth();

        $data = [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $user->factory()->getTTL() * 60
        ];
        return ApiResponse::success($data);
    }
}
