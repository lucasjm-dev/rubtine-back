<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Requests\TaskIndexRequest;
use App\Domains\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Domains\Tasks\Requests\TaskStatusIndexRequest;

class ProfessionalTaskController extends Controller
{

    public function index(TaskStatusIndexRequest $request, TaskService $service)
    {
        return $service->paginateForProfessionalSubcategory($request->validated());
    }

    public function assigned(TaskStatusIndexRequest $request, TaskService $service)
    {
        return $service->paginateAssigned($request->validated());
    }
}
