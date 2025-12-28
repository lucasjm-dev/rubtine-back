<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskRequestStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class TaskService
{

    private QueryPaginator $paginator;

    public function __construct(
        QueryPaginator $paginator
    ) {
        $this->paginator = $paginator;
    }

    public function paginate(array $filters)
    {
        $query = Task::query()->where('user_id', auth()->id())->with('subcategory');

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }

        return $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description', 'status']
        );
    }

    public function paginateAssigned(array $filters)
    {
        /** @var ProfessionalUser $professional */
        $professional = auth()->user()->professionalUser;

        $query = Task::query()->assignedToProfessional($professional)->with('subcategory');

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }

        return $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description']
        );
    }

    public function paginateForProfessionalSubcategory(array $filters)
    {
        /** @var ProfessionalUser $professional */
        $professional = auth()->user()->professionalUser;

        $query = Task::query()
            ->availableForProfessional($professional)
            ->with('subcategory');

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }

        return $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description']
        );
    }


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
