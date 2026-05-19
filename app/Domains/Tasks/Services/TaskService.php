<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Enums\TaskParticipantTaskRole;
use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;
use App\Support\Users\UserProfiles;
use Illuminate\Support\Facades\DB;

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
        $query = Task::query()
            ->ownedByUser(auth()->user())
            ->withListRelations();

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }



        $tasks = $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description', 'status']
        );
        return ApiResponse::success($tasks);
    }

    public function paginateAssigned(array $filters)
    {
        /** @var ProfessionalUser $professional */
        $professional = auth()->user()->professionalUser;

        $query = Task::query()
            ->assignedToProfessional($professional)
            ->withListRelations();

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }

        $tasks = $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description']
        );
        return ApiResponse::success($tasks);
    }

    public function paginateForProfessionalSubcategory(array $filters)
    {
        /** @var ProfessionalUser $professional */
        $professional = auth()->user()->professionalUser;

        $query = Task::query()
            ->availableForProfessional($professional)
            ->withListRelations();

        if (!empty($filters['status'])) {
            $query->whereStatus($filters['status']);
        }

        $tasks = $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'title', 'status'],
            ['title', 'description']
        );
        return ApiResponse::success($tasks);
    }


    public function create(array $data)
    {
        /** @var User $user */
        $user = auth()->user();
        $ownerProfile = $this->inferParticipantProfileFromLogin($user);

        if (! $this->canOwnTasks($user) || ! $ownerProfile) {
            return ApiResponse::error('task_forbidden', null, 403);
        }

        if (array_key_exists('beneficiary_id', $data) && ! $this->beneficiaryIsAccessible($data['beneficiary_id'], $user)) {
            return ApiResponse::error('beneficiary_not_found', null, 404);
        }

        $task = DB::transaction(function () use ($user, $data, $ownerProfile) {
            $task = Task::query()->create($data);
            $task->participants()->create([
                'user_id' => $user->id,
                'task_role' => TaskParticipantTaskRole::OWNER,
                'participant_profile' => $ownerProfile,
                'status' => TaskParticipantStatus::ACCEPTED,
                'requested_by_user_id' => $user->id,
            ]);

            return $task;
        });

        return ApiResponse::success($task->fresh(['subcategory', 'beneficiary']));
    }

    public function update(Task $task, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        if (array_key_exists('beneficiary_id', $data) && ! $this->beneficiaryIsAccessible($data['beneficiary_id'], $user)) {
            return ApiResponse::error('beneficiary_not_found', null, 404);
        }

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $task->update($data);

        return ApiResponse::success($task->fresh(['subcategory', 'beneficiary']));
    }

    public function delete(Task $task)
    {
        /** @var User $user */
        $user = auth()->user();
        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $task->delete();

        return ApiResponse::success();
    }

    private function canOwnTasks(User $user): bool
    {
        return UserProfiles::hasAnyProfile(
            $user,
            TaskParticipantProfile::userProfileTypes()
        );
    }

    private function inferParticipantProfileFromLogin(User $user): ?string
    {
        return TaskParticipantProfile::fromUserLogin($user);
    }

    private function beneficiaryIsAccessible(?int $beneficiaryId, User $user): bool
    {
        if ($beneficiaryId === null) {
            return true;
        }

        return Beneficiary::query()
            ->accessibleToUser($user)
            ->whereKey($beneficiaryId)
            ->exists();
    }
}
