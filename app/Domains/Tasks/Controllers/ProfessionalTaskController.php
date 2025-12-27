<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Requests\TaskIndexRequest;
use App\Domains\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;

class ProfessionalTaskController extends Controller
{

    public function index(TaskIndexRequest $request, TaskService $service)
    {
        return $service->paginateForProfessionalSubcategory($request->validated());
    }

    public function assigned(TaskIndexRequest $request, TaskService $service)
    {
        return $service->paginateAssigned($request->validated());
    }
}
