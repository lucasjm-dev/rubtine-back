<?php

namespace App\Domains\Beneficiaries\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Requests\CreateBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\BeneficiaryIndexRequest;
use App\Domains\Beneficiaries\Requests\UpdateBeneficiaryRequest;
use App\Domains\Beneficiaries\Services\BeneficiaryService;
use App\Http\Controllers\Controller;

class BeneficiaryController extends Controller
{
    public function index(BeneficiaryIndexRequest $request, BeneficiaryService $service)
    {
        return $service->paginate($request->validated());
    }

    public function create(CreateBeneficiaryRequest $request, BeneficiaryService $service)
    {
        return $service->create($request->validated());
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary, BeneficiaryService $service)
    {
        return $service->update($beneficiary, $request->validated());
    }
}
