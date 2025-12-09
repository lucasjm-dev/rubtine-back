<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Models\User;
use App\Domains\Users\Requests\CreateCompanyUserRequest;
use App\Domains\Users\Requests\UpdateCompanyUserRequest;
use App\Domains\Users\Services\CompanyUserService;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;

class CompanyUserController extends Controller
{
    public function create(CreateCompanyUserRequest $request, CompanyUserService $service)
    {
        $data = $request->validated();
        return $service->create($data);
    }

    public function update(UpdateCompanyUserRequest $request, CompanyUserService $service)
    {
        $data = $request->validated();
        return $service->update($data);
    }

    public function delete()
    {
        $companyUser = auth()->user()->companyUser;
        $companyUser->delete();
        $loginAs = auth()->payload()->get('login_as', null);
        if ($loginAs === User::TYPE_COMPANY) {
            return ApiResponse::success(auth()->logout());
        }
        return ApiResponse::success();
    }
}
