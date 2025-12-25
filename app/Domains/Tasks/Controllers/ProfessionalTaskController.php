<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Requests\CreateTaskRequest;
use App\Domains\Tasks\Requests\TaskIndexRequest;
use App\Domains\Tasks\Requests\UpdateTaskRequest;
use App\Domains\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;

class ProfessionalTaskController extends Controller
{

    public function index(TaskIndexRequest $request, TaskService $service)
    {
        return $service->paginate($request->validated());
    }
}
