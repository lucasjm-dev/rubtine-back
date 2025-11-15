<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Auth\Requests\RegisterUserRequest;
use App\Domains\Users\Requests\CreateProfessionalUserRequest;
use App\Domains\Users\Requests\UpdateProfessionalUserRequest;
use App\Domains\Users\Requests\UpdateUserRequest;
use App\Domains\Users\Services\ProfessionalUserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class ProfessionalUserController extends Controller
{
    public function create(RegisterUserRequest $userRequest, CreateProfessionalUserRequest $professionalRequest, ProfessionalUserService $service)
    {

        $userData = $userRequest->validated();
        $professionalData = $professionalRequest->validated();

        return $service->create($userData, $professionalData);
    }



    public function update(UpdateUserRequest $userRequest, UpdateProfessionalUserRequest $professionalUserRequest, ProfessionalUserService $service)
    {
        $data = $userRequest->validated();
        $professionalData = $professionalUserRequest->validated();

        return $service->update($data, $professionalData);
    }

    public function delete()
    {
        $professionalUser = auth()->user()->professionalUser;
        $professionalUser->delete();
        return ApiResponse::success();
    }
}
