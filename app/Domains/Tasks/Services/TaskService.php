<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;

class TaskService
{
    public function create(array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $task = $user->tasks()->create($data);

        return ApiResponse::success($task->fresh());
    }

    public function update(Task $task, array $data)
    {
        /** @var User $user */
        $user = auth()->user();
        $task = $user->ownedTaskOrFail($task->id);

        $task->update($data);

        return ApiResponse::success($task->fresh());
    }

    public function delete(Task $task)
    {
        /** @var User $user */
        $user = auth()->user();
        $task = $user->ownedTaskOrFail($task->id);

        $task->delete();

        return ApiResponse::success();
    }
}
