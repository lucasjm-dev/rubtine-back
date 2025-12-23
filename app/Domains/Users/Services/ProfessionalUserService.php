<?php

namespace App\Domains\Users\Services;

use App\Domains\Tasks\Enums\TaskProfessionalUserStatus;
use App\Domains\Tasks\Models\Pivots\TaskProfessional;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Rules\TaskProfessionalUserTransitions;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\User;
use App\Domains\Users\Resources\ProfessionalUserResource;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class ProfessionalUserService
{
    public function create(array $professionalData)
    {
        /** @var User */
        $user = auth()->user();

        if ($user->professionalUser()->exists()) {
            return ApiResponse::error(
                'professional_user_already_exists',
                null,
                422
            );
        }

        $professionalUser = $user->professionalUser()->create($professionalData);

        $token = Auth::claims([
            'login_as' => User::TYPE_PROFESSIONAL
        ])->login($user);

        return ApiResponse::success([
            'token' => $token,
            'login_as' => User::TYPE_PROFESSIONAL,
            'professional_user' => $professionalUser->fresh()
        ]);
    }

    public function update($professionalData)
    {
        /** @var User */
        $user = auth()->user();

        if (! $professionalUser = $user->professionalUser) {
            return ApiResponse::error("professional_user_not_found", null, 403);
        }
        $professionalUser->update($professionalData);

        return ApiResponse::success(
            new ProfessionalUserResource(
                $professionalUser->fresh()
            )
        );
    }

    public function createRequest(Task $task)
    {
        /** @var ProfessionalUser */
        $professionalUser = auth()->user()->professionalUser;
        $pivot = TaskProfessional::findFor($task, $professionalUser);

        if (! $pivot) {
            $task->professionals()->attach(
                $professionalUser->id,
                ['status' => TaskProfessionalUserStatus::PENDING]
            );
            return ApiResponse::success();
        }

        if (! TaskProfessionalUserTransitions::canTransition(
            $pivot->status,
            TaskProfessionalUserStatus::PENDING
        )) {
            return ApiResponse::error(
                'task_request_status_invalid',
                null,
                422
            );
        }

        $pivot->update([
            'status' => TaskProfessionalUserStatus::PENDING,
        ]);
        return ApiResponse::success();
    }

    public function cancelRequest(Task $task)
    {
        /** @var ProfessionalUser */
        $professionalUser = auth()->user()->professionalUser;
        $pivot = TaskProfessional::findFor($task, $professionalUser);

        if (! $pivot) {
            return ApiResponse::error('task_request_not_found', null, 404);
        }

        if (! TaskProfessionalUserTransitions::canTransition(
            $pivot->status,
            TaskProfessionalUserStatus::CANCELED
        )) {
            return ApiResponse::error(
                'task_request_status_invalid',
                null,
                422
            );
        }

        $task->professionals()->updateExistingPivot(
            $professionalUser->id,
            ['status' => TaskProfessionalUserStatus::CANCELED]
        );

        return ApiResponse::success();
    }
}
