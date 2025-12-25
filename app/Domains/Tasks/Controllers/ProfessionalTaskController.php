<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Requests\BaseIndexRequest;

class ProfessionalTaskController extends Controller
{

    public function index(BaseIndexRequest $request, TaskService $service)
    {
        return $service->paginateAssigned($request->validated());
    }
}
