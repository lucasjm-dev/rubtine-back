<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskEventSchedule;
use App\Domains\Tasks\Requests\CreateTaskEventScheduleRequest;
use App\Domains\Tasks\Requests\TaskEventScheduleIndexRequest;
use App\Domains\Tasks\Requests\UpdateTaskEventScheduleRequest;
use App\Domains\Tasks\Services\TaskEventScheduleService;
use App\Http\Controllers\Controller;

class TaskEventScheduleController extends Controller
{
    private TaskEventScheduleService $service;

    public function __construct(TaskEventScheduleService $service)
    {
        $this->service = $service;
    }

    public function index(TaskEventScheduleIndexRequest $request, Task $task)
    {
        return $this->service->paginate($request->validated(), $task);
    }

    public function create(CreateTaskEventScheduleRequest $request, Task $task)
    {
        return $this->service->create($task, $request->validated());
    }

    public function show(Task $task, TaskEventSchedule $schedule)
    {
        return $this->service->show($task, $schedule);
    }

    public function update(UpdateTaskEventScheduleRequest $request, Task $task, TaskEventSchedule $schedule)
    {
        return $this->service->update($task, $schedule, $request->validated());
    }

    public function delete(Task $task, TaskEventSchedule $schedule)
    {
        return $this->service->delete($task, $schedule);
    }
}
