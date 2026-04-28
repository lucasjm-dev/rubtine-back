<?php

namespace App\Domains\Tasks\Controllers;

use App\Domains\Tasks\Models\TaskParticipant;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Requests\CreateTaskParticipantRequest;
use App\Domains\Tasks\Requests\TaskParticipantIndexRequest;
use App\Domains\Tasks\Services\TaskParticipantService;
use App\Http\Controllers\Controller;

class TaskParticipantController extends Controller
{

    public function index(TaskParticipantIndexRequest $request, TaskParticipantService $service)
    {
        return $service->paginate($request->validated());
    }

    public function create(CreateTaskParticipantRequest $request, Task $task, TaskParticipantService $service)
    {
        return $service->create($task, $request->validated());
    }

    public function cancel(TaskParticipant $taskParticipant, TaskParticipantService $service)
    {
        return $service->cancel($taskParticipant);
    }

    public function accept(TaskParticipant $taskParticipant, TaskParticipantService $service)
    {
        return $service->accept($taskParticipant);
    }

    public function reject(TaskParticipant $taskParticipant, TaskParticipantService $service)
    {
        return $service->reject($taskParticipant);
    }
}
