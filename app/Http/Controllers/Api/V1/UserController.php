<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;

class UserController extends Controller
{
    // all users
    // public function index()
    // {
    //     $users = User::paginate(10);
    //     return ApiResponse::success($users);
    // }

    public function update(UpdateUserRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $data = $request->validated();

        $user->update($data);

        return ApiResponse::success($user->fresh());
    }



    public function delete()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->delete();
        return ApiResponse::success();
    }
}
