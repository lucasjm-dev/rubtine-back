<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskEvent;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class TaskEventService
{

    private QueryPaginator $paginator;

    public function __construct(
        QueryPaginator $paginator
    ) {
        $this->paginator = $paginator;
    }

    public function paginate(array $data, Task $task)
    {
        /** @var User $user */
        $user = auth()->user();

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $query = $task->events()
            ->with('notifications')
            ->orderBy('scheduled_at')
            ->getQuery();

        $events = $this->paginator->paginate($query, $data);

        return ApiResponse::success($events);
    }

    public function getAll(array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $query = TaskEvent::query()
            ->ownedByUser($user)
            ->with('task', 'task.beneficiary', 'notifications')
            ->orderBy('scheduled_at');

        $events = $this->paginator->paginate($query, $data);

        return ApiResponse::success($events);
    }

    public function show(Task $task, TaskEvent $event)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        $event->load('notifications');

        return ApiResponse::success($event);
    }

    public function create(Task $task, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $data['user_id'] = $user->id;

        $event = $task->events()->create($data);

        return ApiResponse::success($event->fresh('notifications'));
    }

    public function update(Task $task, TaskEvent $event, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        $event->update($data);

        return ApiResponse::success($event->fresh('notifications'));
    }

    public function delete(Task $task, TaskEvent $event)
    {
        /** @var User $user */
        $user = auth()->user();

        Task::query()->ownedByUser($user)->findOrFail($task->id);

        $event->delete();

        return ApiResponse::success();
    }
}
