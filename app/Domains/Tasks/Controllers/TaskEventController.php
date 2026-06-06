<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskEvent;
use App\Domains\Tasks\Requests\CreateTaskEventRequest;
use App\Domains\Tasks\Requests\UpdateTaskEventRequest;
use App\Domains\Tasks\Services\TaskEventService;
use App\Http\Controllers\Controller;
use App\Domains\Tasks\Requests\TaskEventIndexRequest;

class TaskEventController extends Controller
{
    private TaskEventService $service;

    public function __construct(TaskEventService $service)
    {
        $this->service = $service;
    }

    public function index(TaskEventIndexRequest $request, Task $task)
    {
        return $this->service->paginate($request->validated(), $task);
    }

    public function getAll(TaskEventIndexRequest $request)
    {
        return $this->service->getAll($request->validated());
    }

    public function create(CreateTaskEventRequest $request, Task $task)
    {
        return $this->service->create($task, $request->validated());
    }

    public function show(Task $task, TaskEvent $event)
    {
        return $this->service->show($task, $event);
    }

    public function update(UpdateTaskEventRequest $request, Task $task, TaskEvent $event)
    {
        return $this->service->update($task, $event, $request->validated());
    }

    public function delete(Task $task, TaskEvent $event)
    {
        return $this->service->delete($task, $event);
    }
}
