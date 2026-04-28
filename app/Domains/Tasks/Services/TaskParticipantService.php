<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskParticipant;
use App\Domains\Tasks\Rules\TaskParticipantTransitions;
use App\Domains\Tasks\Validators\CreateParticipantContext;
use App\Domains\Tasks\Validators\TaskParticipantValidator;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class TaskParticipantService
{
    private QueryPaginator $paginator;
    private TaskParticipantValidator $validator;

    public function __construct(
        QueryPaginator $paginator,
        TaskParticipantValidator $validator
    ) {
        $this->paginator = $paginator;
        $this->validator = $validator;
    }

    public function paginate(array $filters)
    {
        $query = TaskParticipant::query()
            ->where('role', '!=', TaskParticipantRole::OWNER);
        /** @var User  **/
        $user = auth()->user();
        $query->relevantToUser($user);

        $query->withStatus($filters['status'] ?? null);
        $query->with([
            'user.simpleUser',
            'user.professionalUser'
        ]);

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

    public function create(Task $task, array $data = [])
    {
        /** @var User $user */
        $user = auth()->user();

        $ctx = $this->buildCreateContext($task, $user, $data);

        if (! $ctx) {
            return ApiResponse::error('task_participant_invalid_request', null, 422);
        }

        $error = $this->validator->validateCreate($ctx);

        if ($error) {
            return ApiResponse::error($error, null, 422);
        }

        return $this->upsertParticipant($task, $ctx);
    }

    public function cancel(TaskParticipant $taskParticipant)
    {
        /** @var User $user */
        $user = auth()->user();

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


    private function buildCreateContext(Task $task, User $actor, array $data): ?CreateParticipantContext
    {
        $targetUser = $this->resolveTargetUser($actor, $data);

        if (! $targetUser) {
            return null;
        }

        $role = $data['role'] ?? $this->inferRoleFromLogin($actor);

        if (! $role) {
            return null;
        }

        return new CreateParticipantContext($task, $actor, $targetUser, $role);
    }

    private function resolveTargetUser(User $actor, array $data): ?User
    {
        $targetUserId = $data['user_id'] ?? null;

        if (! $targetUserId) {
            return $actor;
        }

        return User::query()->find($targetUserId);
    }

    /**
     * When no role is explicitly provided, infer it from the actor's login type.
     * Returns null if the login type doesn't map to a participant role.
     */
    private function inferRoleFromLogin(User $actor): ?string
    {
        if ($actor->isLoggedAsProfessional()) {
            return TaskParticipantRole::PROFESSIONAL;
        }

        if ($actor->isLoggedAsSimple()) {
            return TaskParticipantRole::SIMPLE;
        }

        return null;
    }

    private function upsertParticipant(Task $task, CreateParticipantContext $ctx)
    {
        $existing = TaskParticipant::findForTaskAndUser($task, $ctx->targetUser, $ctx->role);

        if (! $existing) {
            $task->participants()->create([
                'user_id' => $ctx->targetUser->id,
                'role' => $ctx->role,
                'status' => TaskParticipantStatus::PENDING,
                'requested_by_user_id' => $ctx->actor->id,
            ]);

            return ApiResponse::success();
        }

        if (! TaskParticipantTransitions::canPending($existing)) {
            return ApiResponse::error('task_participant_status_invalid', null, 422);
        }

        $existing->status = TaskParticipantStatus::PENDING;
        $existing->requested_by_user_id = $ctx->actor->id;
        $existing->save();

        return ApiResponse::success();
    }

    private function canRespondToParticipant(User $user, TaskParticipant $taskParticipant): bool
    {
        $task = $taskParticipant->task;

        if (! $task || $taskParticipant->isOwner()) {
            return false;
        }

        // The requester cannot accept/reject their own request
        if ($taskParticipant->requested_by_user_id === $user->id) {
            return false;
        }

        // If the participant requested it themselves, only the owner can respond
        if ($taskParticipant->requested_by_user_id === $taskParticipant->user_id) {
            return $task->isOwnedBy($user);
        }

        // Otherwise, only the target user can respond
        return $taskParticipant->user_id === $user->id;
    }
}
