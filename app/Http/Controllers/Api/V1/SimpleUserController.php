<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\SimpleUser\CreateSimpleUserRequest;
use App\Http\Requests\SimpleUser\UpdateSimpleUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\SimpleUser;
use App\Models\User;

class SimpleUserController extends Controller
{
    public function create(RegisterUserRequest $userRequest, CreateSimpleUserRequest $simpleRequest)
    {
        $simpleData = $simpleRequest->validated();
        if (!empty($simpleData['user_id'])) {
            $user = User::findOrFail($simpleData['user_id']);
        } else {
            $userData = $userRequest->validated();
            $userData['password'] = bcrypt($userData['password']);
            $user = User::create($userData);
        }

        $simpleUser = SimpleUser::firstOrCreate(
            ['user_id' => $user->id],
            $simpleData
        );

        $token = auth()->claims(['login_as' => User::TYPE_SIMPLE])->login($user);
        return $this->respondWithToken($token);
    }


    public function update(UpdateUserRequest $userRequest, UpdateSimpleUserRequest $simpleUserRequest)
    {
        /** @var User */
        $user = auth()->user();
        $data = $userRequest->validated();
        if (!empty($data)) {
            $user->update($data);
        }

        $simpleData = $simpleUserRequest->validated();
        $simpleUser = $user->simpleUser;
        if ($simpleUser) $simpleUser->update($simpleData);

        return ApiResponse::success([
            'user' => $user->fresh(),
            'simple_user' => $simpleUser->fresh(),
        ]);
    }

    public function delete()
    {
        $simpleUser = auth()->user()->simpleUser;
        $simpleUser->delete();
        return ApiResponse::success();
    }
}
