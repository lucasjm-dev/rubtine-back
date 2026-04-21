<?php

namespace App\Domains\Patients\Services;

use App\Domains\Patients\Models\Patient;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class PatientService
{
    private QueryPaginator $paginator;

    public function __construct(
        QueryPaginator $paginator
    ) {
        $this->paginator = $paginator;
    }

    public function paginate(array $filters)
    {
        /** @var User $user */
        $user = auth()->user();

        $query = Patient::query()->accessibleToUser($user);

        return $this->paginator->paginate(
            $query,
            $filters,
            ['id', 'created_at', 'updated_at', 'name', 'last_name', 'phone', 'email'],
            ['name', 'last_name', 'phone', 'email']
        );
    }

    public function create(array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $this->canManagePatients($user)) {
            return ApiResponse::error('patient_forbidden', null, 403);
        }

        if (! $this->linkedUserIsValid($data['user_id'] ?? null)) {
            return ApiResponse::error('patient_user_invalid', null, 422);
        }

        $patient = Patient::query()
            ->where('created_by_user_id', $user->id)
            ->where('name', $data['name'])
            ->where('last_name', $data['last_name'])
            ->where('phone', $data['phone'])
            ->first();

        if ($patient) {
            $patient->fill([
                'email' => $data['email'] ?? $patient->email,
                'user_id' => $data['user_id'] ?? $patient->user_id,
            ]);
            $patient->save();

            return ApiResponse::success($patient->fresh());
        }

        $data['created_by_user_id'] = $user->id;
        $patient = Patient::query()->create($data);

        return ApiResponse::success($patient->fresh());
    }

    public function update(Patient $patient, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $patient = Patient::query()
            ->accessibleToUser($user)
            ->findOrFail($patient->id);

        if (array_key_exists('user_id', $data) && ! $this->linkedUserIsValid($data['user_id'])) {
            return ApiResponse::error('patient_user_invalid', null, 422);
        }

        $patient->update($data);

        return ApiResponse::success($patient->fresh());
    }

    private function canManagePatients(User $user): bool
    {
        return (bool) $user->isLoggedAsProfessional();
    }

    private function linkedUserIsValid(?int $userId): bool
    {
        if ($userId === null) {
            return true;
        }

        $user = User::query()->find($userId);

        return (bool) $user && $user->simpleUser()->exists();
    }
}
