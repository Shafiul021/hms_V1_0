<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdmissionRequest;
use App\Http\Resources\AdmissionResource;
use App\Models\Admission;
use App\Services\IpdService;
use Illuminate\Http\JsonResponse;

class AdmissionController extends Controller
{
    /**
     * Admit a patient to a bed.
     *
     * POST /api/admissions
     * Role: admin|doctor
     */
    public function store(StoreAdmissionRequest $request, IpdService $service): AdmissionResource
    {
        $admission = $service->admit($request->validated());

        return new AdmissionResource($admission);
    }

    /**
     * Discharge a patient — frees the bed automatically.
     *
     * PATCH /api/admissions/{id}/discharge
     * Role: admin|doctor
     */
    public function discharge(int $id, IpdService $service): AdmissionResource
    {
        $admission = Admission::with(['patient.user', 'doctor.user', 'bed.ward'])->findOrFail($id);
        $discharged = $service->discharge($admission);

        return new AdmissionResource($discharged);
    }
}
