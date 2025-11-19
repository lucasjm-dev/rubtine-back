<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Models\User;
use App\Domains\Users\Requests\CreateSimpleUserRequest;
use App\Domains\Users\Requests\UpdateSimpleUserRequest;
use App\Domains\Users\Services\SimpleUserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class SimpleUserController extends Controller
{
    public function create(CreateSimpleUserRequest $simpleRequest, SimpleUserService $service)
    {
        $simpleData = $simpleRequest->validated();
        return $service->create($simpleData);
    }


    public function update(UpdateSimpleUserRequest $simpleRequest, SimpleUserService $service)
    {
        $simpleData = $simpleRequest->validated();
        return $service->update($simpleData);
    }

    public function delete()
    {
        $simpleUser = auth()->user()->simpleUser;
        $simpleUser->delete();
        $loginAs = auth()->payload()->get('login_as', null);
        if ($loginAs === User::TYPE_SIMPLE) {
            return ApiResponse::success(auth()->logout());
        }
        return ApiResponse::success();
    }
}
