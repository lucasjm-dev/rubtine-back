<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Auth\Requests\RegisterUserRequest;
use App\Domains\Users\Requests\CreateSimpleUserRequest;
use App\Domains\Users\Requests\UpdateSimpleUserRequest;
use App\Domains\Users\Requests\UpdateUserRequest;
use App\Domains\Users\Services\SimpleUserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class SimpleUserController extends Controller
{
    public function create(RegisterUserRequest $userRequest, CreateSimpleUserRequest $simpleRequest, SimpleUserService $service)
    {
        $userData = $userRequest->validated();
        $simpleData = $simpleRequest->validated();

        return $service->create($userData, $simpleData);
    }


    public function update(UpdateUserRequest $userRequest, UpdateSimpleUserRequest $simpleRequest, SimpleUserService $service)
    {
        $data = $userRequest->validated();
        $simpleData = $simpleRequest->validated();

        return $service->update($data, $simpleData);
    }

    public function delete()
    {
        $simpleUser = auth()->user()->simpleUser;
        $simpleUser->delete();
        return ApiResponse::success();
    }
}
