<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Users\Models\User;
use App\Helpers\ApiResponse;
use App\Support\Query\QueryPaginator;

class BeneficiaryService
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

        $query = Beneficiary::query()->accessibleToUser($user);

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

        if (! $this->canManageBeneficiaries($user)) {
            return ApiResponse::error('beneficiary_forbidden', null, 403);
        }

        if (! $this->linkedUserIsValid($data['user_id'] ?? null)) {
            return ApiResponse::error('beneficiary_user_invalid', null, 422);
        }

        $beneficiary = Beneficiary::query()
            ->where('created_by_user_id', $user->id)
            ->where('name', $data['name'])
            ->where('last_name', $data['last_name'])
            ->where('phone', $data['phone'])
            ->first();

        if ($beneficiary) {
            $beneficiary->fill([
                'email' => $data['email'] ?? $beneficiary->email,
                'user_id' => $data['user_id'] ?? $beneficiary->user_id,
            ]);
            $beneficiary->save();

            return ApiResponse::success($beneficiary->fresh());
        }

        $data['created_by_user_id'] = $user->id;
        $beneficiary = Beneficiary::query()->create($data);

        return ApiResponse::success($beneficiary->fresh());
    }

    public function update(Beneficiary $beneficiary, array $data)
    {
        /** @var User $user */
        $user = auth()->user();

        $beneficiary = Beneficiary::query()
            ->accessibleToUser($user)
            ->findOrFail($beneficiary->id);

        if (array_key_exists('user_id', $data) && ! $this->linkedUserIsValid($data['user_id'])) {
            return ApiResponse::error('beneficiary_user_invalid', null, 422);
        }

        $beneficiary->update($data);

        return ApiResponse::success($beneficiary->fresh());
    }

    private function canManageBeneficiaries(User $user): bool
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
