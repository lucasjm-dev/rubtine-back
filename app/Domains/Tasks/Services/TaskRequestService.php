<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskRequestStatus;
use App\Domains\Tasks\Models\Pivots\TaskRequest;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Rules\TaskRequestRules;
use App\Domains\Tasks\Rules\TaskRequestTransitions;
use App\Domains\Users\Models\ProfessionalUser;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class TaskRequestService
{

    private QueryPaginator $paginator;

    public function __construct(
        QueryPaginator $paginator
    ) {
        $this->paginator = $paginator;
    }

    public function paginate(array $filters)
    {
        $query = TaskRequest::query();
        /** @var User  **/
        $user = auth()->user();

        if ($user->isLoggedAsProfessional()) {
            $query->forProfessional($user->professionalUser);
        }

        if ($user->isLoggedAsSimple()) {
            $query->forSimpleUser($user);
        }

        $query->withStatus($filters['status'] ?? null);

        if (!empty($filters['search'])) {
            $query->whereHas('task', function ($q) use ($filters) {
                $q->where('title', 'ILIKE', '%' . $filters['search'] . '%');
            });
        }

        return $this->paginator->paginate(
            $query->with('task'),
            $filters,
            ['id', 'status'],
            []
        );
    }



    public function create(Task $task)
    {
        /** @var ProfessionalUser */
        $professionalUser = auth()->user()->professionalUser;
        $taskRequest = TaskRequest::findFor($task, $professionalUser);

        if (! TaskRequestRules::canCreate($task, $professionalUser)) {
            return ApiResponse::error('task_request_not_found', null, 404);
        }

        if (! $taskRequest) {
            $task->professionals()->attach(
                $professionalUser->id,
                ['status' => TaskRequestStatus::PENDING]
            );
            return ApiResponse::success();
        }

        if (! TaskRequestTransitions::canPending($taskRequest)) {
            return ApiResponse::error('task_request_status_invalid', null, 422);
        }

        $taskRequest->status = TaskRequestStatus::PENDING;
        $taskRequest->save();

        return ApiResponse::success();
    }

    public function cancel(Task $task)
    {
        /** @var ProfessionalUser */
        $professionalUser = auth()->user()->professionalUser;
        $taskRequest = TaskRequest::findFor($task, $professionalUser);

        if (! $taskRequest) {
            return ApiResponse::error('task_request_not_found', null, 404);
        }

        if (! TaskRequestTransitions::canCancel($taskRequest)) {
            return ApiResponse::error('task_request_status_invalid', null, 422);
        }

        $taskRequest->status = TaskRequestStatus::CANCELED;
        $taskRequest->save();

        return ApiResponse::success();
    }

    public function accept(TaskRequest $taskRequest)
    {
        /** @var User */
        $user = auth()->user();
        $task = $taskRequest->task;

        if (! $task || ! $task->isOwnedBy($user)) {
            return ApiResponse::error('task_request_not_found', null, 422);
        }

        if (! TaskRequestTransitions::canAccept($taskRequest)) {
            return ApiResponse::error('task_request_status_invalid', null, 422);
        }

        $taskRequest->status = TaskRequestStatus::ACCEPTED;
        $taskRequest->save();

        return ApiResponse::success();
    }

    public function reject(TaskRequest $taskRequest)
    {
        /** @var User */
        $user = auth()->user();
        $task = $taskRequest->task;

        if (! $task || ! $task->isOwnedBy($user)) {
            return ApiResponse::error('task_request_not_found', null, 422);
        }

        if (! TaskRequestTransitions::canReject($taskRequest)) {
            return ApiResponse::error('task_request_status_invalid', null, 422);
        }

        $taskRequest->status = TaskRequestStatus::REJECTED;
        $taskRequest->save();

        return ApiResponse::success();
    }
}
