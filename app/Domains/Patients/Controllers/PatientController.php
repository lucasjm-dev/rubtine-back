<?php

namespace App\Domains\Patients\Controllers;

use App\Domains\Patients\Models\Patient;
use App\Domains\Patients\Requests\CreatePatientRequest;
use App\Domains\Patients\Requests\PatientIndexRequest;
use App\Domains\Patients\Requests\UpdatePatientRequest;
use App\Domains\Patients\Services\PatientService;
use App\Http\Controllers\Controller;

class PatientController extends Controller
{
    public function index(PatientIndexRequest $request, PatientService $service)
    {
        return $service->paginate($request->validated());
    }

    public function create(CreatePatientRequest $request, PatientService $service)
    {
        return $service->create($request->validated());
    }

    public function update(UpdatePatientRequest $request, Patient $patient, PatientService $service)
    {
        return $service->update($patient, $request->validated());
    }
}
