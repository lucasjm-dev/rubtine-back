<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\Pivots\TaskRequest;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Requests\TaskRequestIndexRequest;
use App\Domains\Tasks\Services\TaskRequestService;
use App\Http\Controllers\Controller;

class TaskRequestController extends Controller
{

    public function index(TaskRequestIndexRequest $request, TaskRequestService $service)
    {
        return $service->paginate($request->validated());
    }

    public function create(Task $task, TaskRequestService $service)
    {
        return $service->create($task);
    }

    public function cancel(Task $task, TaskRequestService $service)
    {
        return $service->cancel($task);
    }

    public function accept(TaskRequest $taskRequest, TaskRequestService $service)
    {
        return $service->accept($taskRequest);
    }

    public function reject(TaskRequest $taskRequest, TaskRequestService $service)
    {
        return $service->reject($taskRequest);
    }
}
