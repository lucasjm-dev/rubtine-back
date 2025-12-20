<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Requests\CreateTaskRequest;
use App\Domains\Tasks\Requests\UpdateTaskRequest;
use App\Domains\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;

class TaskController extends Controller
{
    public function create(CreateTaskRequest $request, TaskService $service)
    {
        return $service->create($request->validated());
    }

    public function update(UpdateTaskRequest $request, TaskService $service, Task $task)
    {
        return $service->update($task, $request->validated());
    }

    public function delete(Task $task, TaskService $service)
    {
        return $service->delete($task);
    }
}
