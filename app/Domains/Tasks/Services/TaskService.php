<?php

namespace App\Domains\Tasks\Services;

use App\Domains\Patients\Models\Patient;
use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;
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
            ->with(['subcategory', 'patient']);

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

        $query = Task::query()->assignedToProfessional($professional)->with(['subcategory', 'patient']);

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
            ->with(['subcategory', 'patient']);

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

        if (! $this->canOwnTasks($user)) {
            return ApiResponse::error('task_forbidden', null, 403);
        }

        if (array_key_exists('patient_id', $data) && ! $this->patientIsAccessible($data['patient_id'], $user)) {
            return ApiResponse::error('patient_not_found', null, 404);
        }

        $task = DB::transaction(function () use ($user, $data) {
            $task = Task::query()->create($data);
            $task->participants()->create([
                'user_id' => $user->id,
                'role' => TaskParticipantRole::OWNER,
                'status' => TaskParticipantStatus::ACCEPTED,
                'requested_by_user_id' => $user->id,
            ]);

            return $task;
        });

        return ApiResponse::success($task->fresh(['subcategory', 'patient']));
    }

    public function update(Task $task, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        if (array_key_exists('patient_id', $data) && ! $this->patientIsAccessible($data['patient_id'], $user)) {
            return ApiResponse::error('patient_not_found', null, 404);
        }

        $task = Task::query()->ownedByUser($user)->findOrFail($task->id);

        $task->update($data);

        return ApiResponse::success($task->fresh(['subcategory', 'patient']));
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
        return (bool) $user->simpleUser || (bool) $user->professionalUser;
    }

    private function patientIsAccessible(?int $patientId, User $user): bool
    {
        if ($patientId === null) {
            return true;
        }

        return Patient::query()
            ->accessibleToUser($user)
            ->whereKey($patientId)
            ->exists();
    }
}
