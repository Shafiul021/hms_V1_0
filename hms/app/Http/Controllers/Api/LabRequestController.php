<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabRequestRequest;
use App\Http\Resources\LabRequestResource;
use App\Services\OpdService;

class LabRequestController extends Controller
{
    /**
     * Store a newly created lab request for an appointment.
     *
     * POST /api/lab-requests
     * Role: doctor
     */
    public function store(StoreLabRequestRequest $request, OpdService $service): LabRequestResource
    {
        $doctor     = auth()->user()->doctor;
        $labRequest = $service->createLabRequest($request->validated(), $doctor->id);

        return new LabRequestResource($labRequest);
    }
}
