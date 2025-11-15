<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Requests\UpdateUserRequest;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;


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
        /** @var User  */
        $user = auth()->user();
        $data = $request->validated();

        $user->update($data);

        return ApiResponse::success($user->fresh());
    }



    public function delete()
    {
        /** @var User $user */
        $user = auth()->user();

        $user->delete();
        return ApiResponse::success();
    }
}
