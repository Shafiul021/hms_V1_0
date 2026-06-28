<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiagnosisRequest;
use App\Http\Resources\DiagnosisResource;
use App\Models\Appointment;
use App\Services\OpdService;

class DiagnosisController extends Controller
{
    /**
     * Store a newly created diagnosis for an appointment.
     *
     * POST /api/diagnoses
     * Role: doctor
     */
    public function store(StoreDiagnosisRequest $request, OpdService $service): DiagnosisResource
    {
        $doctor    = auth()->user()->doctor;
        $diagnosis = $service->createDiagnosis($request->validated(), $doctor->id);

        $diagnosis->load('doctor.user', 'patient.user', 'appointment');

        return new DiagnosisResource($diagnosis);
    }
}
