<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;
use App\Domains\Users\Requests\CreateProfessionalUserRequest;
use App\Domains\Users\Requests\RequestAccessRequest;
use App\Domains\Users\Requests\UpdateProfessionalUserRequest;
use App\Domains\Users\Services\ProfessionalUserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class ProfessionalUserController extends Controller
{
    public function create(CreateProfessionalUserRequest $professionalRequest, ProfessionalUserService $service)
    {
        $professionalData = $professionalRequest->validated();
        return $service->create($professionalData);
    }

    public function update(UpdateProfessionalUserRequest $professionalUserRequest, ProfessionalUserService $service)
    {
        $professionalData = $professionalUserRequest->validated();
        return $service->update($professionalData);
    }

    public function delete()
    {
        $professionalUser = auth()->user()->professionalUser;
        $professionalUser->delete();
        $loginAs = auth()->payload()->get('login_as', null);
        if ($loginAs === User::TYPE_PROFESSIONAL) {
            return ApiResponse::success(auth()->logout());
        }
        return ApiResponse::success();
    }

    public function createRequest(Task $task, RequestAccessRequest $request, ProfessionalUserService $service)
    {
        return $service->createRequest($task);
    }

    public function cancelRequest(Task $task, ProfessionalUserService $service)
    {
        return $service->cancelRequest($task);
    }
}
