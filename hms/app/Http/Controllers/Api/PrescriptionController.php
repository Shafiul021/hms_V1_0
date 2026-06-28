<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\OpdService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrescriptionController extends Controller
{
    /**
     * Store a newly created prescription with items for an appointment.
     *
     * POST /api/prescriptions
     * Role: doctor
     */
    public function store(StorePrescriptionRequest $request, OpdService $service): PrescriptionResource
    {
        $doctor       = auth()->user()->doctor;
        $prescription = $service->createPrescription($request->validated(), $doctor->id);

        return new PrescriptionResource($prescription);
    }

    /**
     * Display the specified prescription with items.
     *
     * GET /api/prescriptions/{id}
     * Role: admin|doctor|patient|nurse|receptionist
     */
    public function show(int $id): PrescriptionResource
    {
        $prescription = Prescription::with([
            'doctor.user',
            'patient.user',
            'appointment',
            'items.medicine',
        ])->findOrFail($id);

        return new PrescriptionResource($prescription);
    }
}
