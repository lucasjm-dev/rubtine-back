<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskParticipant;
use App\Domains\Tasks\Rules\TaskParticipantRules;
use App\Domains\Tasks\Rules\TaskParticipantTransitions;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class TaskParticipantService
{

    private QueryPaginator $paginator;

    public function __construct(
        QueryPaginator $paginator
    ) {
        $this->paginator = $paginator;
    }

    public function paginate(array $filters)
    {
        $query = TaskParticipant::query()
            ->where('role', '!=', TaskParticipantRole::OWNER);
        /** @var User  **/
        $user = auth()->user();
        $query->relevantToUser($user);

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
        /** @var User $user */
        $user = auth()->user();
        $role = $this->currentParticipantRole($user);

        if (! $role) {
            return ApiResponse::error('task_participant_invalid_actor', null, 403);
        }

        $taskParticipant = TaskParticipant::findForTaskAndUser($task, $user, $role);

        if (! TaskParticipantRules::canCreate($task, $user, $role)) {
            return ApiResponse::error('task_participant_forbidden', null, 403);
        }

        if (! $taskParticipant) {
            $task->participants()->create([
                'user_id' => $user->id, //TODO: Check this. If simple user, should be linked user id
                'role' => $role,
                'status' => TaskParticipantStatus::PENDING,
                'requested_by_user_id' => $user->id,
            ]);

            return ApiResponse::success();
        }

        if (! TaskParticipantTransitions::canPending($taskParticipant)) {
            return ApiResponse::error('task_participant_status_invalid', null, 422);
        }

        $taskParticipant->status = TaskParticipantStatus::PENDING;
        $taskParticipant->requested_by_user_id = $user->id;
        $taskParticipant->save();

        return ApiResponse::success();
    }

    public function cancel(Task $task)
    {
        /** @var User $user */
        $user = auth()->user();
        $role = $this->currentParticipantRole($user);

        if (! $role) {
            return ApiResponse::error('task_participant_invalid_actor', null, 403);
        }

        $taskParticipant = TaskParticipant::findForTaskAndUser($task, $user, $role);

        if (! $taskParticipant) {
            return ApiResponse::error('task_participant_not_found', null, 404);
        }

        if ($taskParticipant->requested_by_user_id !== $user->id) {
            return ApiResponse::error('task_participant_forbidden', null, 403);
        }

        if (! TaskParticipantTransitions::canCancel($taskParticipant)) {
            return ApiResponse::error('task_participant_status_invalid', null, 422);
        }

        $taskParticipant->status = TaskParticipantStatus::CANCELED;
        $taskParticipant->save();

        return ApiResponse::success();
    }

    public function accept(TaskParticipant $taskParticipant)
    {
        /** @var User */
        $user = auth()->user();

        if (! $this->canRespondToParticipant($user, $taskParticipant)) {
            return ApiResponse::error('task_participant_forbidden', null, 403);
        }

        if (! TaskParticipantTransitions::canAccept($taskParticipant)) {
            return ApiResponse::error('task_participant_status_invalid', null, 422);
        }

        $taskParticipant->status = TaskParticipantStatus::ACCEPTED;
        $taskParticipant->save();

        return ApiResponse::success();
    }

    public function reject(TaskParticipant $taskParticipant)
    {
        /** @var User */
        $user = auth()->user();

        if (! $this->canRespondToParticipant($user, $taskParticipant)) {
            return ApiResponse::error('task_participant_forbidden', null, 403);
        }

        if (! TaskParticipantTransitions::canReject($taskParticipant)) {
            return ApiResponse::error('task_participant_status_invalid', null, 422);
        }

        $taskParticipant->status = TaskParticipantStatus::REJECTED;
        $taskParticipant->save();

        return ApiResponse::success();
    }

    private function currentParticipantRole(User $user): ?string
    {
        if ($user->isLoggedAsProfessional()) {
            return TaskParticipantRole::PROFESSIONAL;
        }

        if ($user->isLoggedAsSimple()) {
            return TaskParticipantRole::SIMPLE;
        }

        return null;
    }

    private function canRespondToParticipant(
        User $user,
        TaskParticipant $taskParticipant
    ): bool {
        $task = $taskParticipant->task;

        if (! $task || $taskParticipant->isOwner()) {
            return false;
        }

        if ($taskParticipant->requested_by_user_id === $user->id) {
            return false;
        }

        if ($taskParticipant->requested_by_user_id === $taskParticipant->user_id) {
            return $task->isOwnedBy($user);
        }

        return $taskParticipant->user_id === $user->id;
    }
}
