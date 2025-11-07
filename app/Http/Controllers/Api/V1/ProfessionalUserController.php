<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\ProfessionalUser\CreateProfessionalUserRequest;
use App\Http\Requests\ProfessionalUser\UpdateProfessionalUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\ProfessionalUser;
use App\Models\User;

class ProfessionalUserController extends Controller
{
    public function create(RegisterUserRequest $userRequest, CreateProfessionalUserRequest $proffesionalRequest)
    {
        $professionalData = $proffesionalRequest->validated();
        if (!empty($professionalData['user_id'])) {
            $user = User::findOrFail($professionalData['user_id']);
        } else {
            $userData = $userRequest->validated();
            $userData['password'] = bcrypt($userData['password']);
            $user = User::create($userData);
        }

        $professionalUser = ProfessionalUser::firstOrCreate(
            ['user_id' => $user->id],
            $professionalData
        );

        $token = auth()->claims(['login_as' => User::TYPE_PROFESSIONAL])->login($user);
        return $this->respondWithToken($token);
    }


    public function update(UpdateUserRequest $userRequest, UpdateProfessionalUserRequest $professionalUserRequest)
    {
        /** @var User */
        $user = auth()->user();
        $data = $userRequest->validated();
        if (!empty($data)) {
            $user->update($data);
        }

        $professionalData = $professionalUserRequest->validated();
        $professionalUser = $user->professionalUser;
        if ($professionalUser) $professionalUser->update($professionalData);

        return ApiResponse::success([
            'user' => $user->fresh(),
            'professional_user' => $professionalUser->fresh(),
        ]);
    }

    public function delete()
    {
        $professionalUser = auth()->user()->professionalUser;
        $professionalUser->delete();
        return ApiResponse::success();
    }
}
