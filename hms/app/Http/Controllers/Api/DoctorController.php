<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorScheduleRequest;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\TimeSlotResource;
use App\Models\Doctor;
use App\Services\DoctorService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors.
     */
    public function index(): AnonymousResourceCollection
    {
        $doctors = Doctor::with('user')->get();

        return DoctorResource::collection($doctors);
    }

    /**
     * Store a newly created doctor.
     */
    public function store(StoreDoctorRequest $request, DoctorService $service): DoctorResource
    {
        $doctor = $service->create($request->validated());

        return new DoctorResource($doctor);
    }

    /**
     * Display the specified doctor.
     */
    public function show(int $id): DoctorResource
    {
        $doctor = Doctor::with(['user', 'schedules.slots'])->findOrFail($id);

        return new DoctorResource($doctor);
    }

    /**
     * Display available time slots for the doctor.
     */
    public function slots(int $id, Request $request, DoctorService $service): AnonymousResourceCollection
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $slots = $service->getAvailableSlots($id, $request->query('date'));

        return TimeSlotResource::collection($slots);
    }

    /**
     * Update the weekly schedule for the doctor.
     */
    public function updateSchedule(UpdateDoctorScheduleRequest $request, int $id, DoctorService $service): DoctorResource
    {
        $doctor = Doctor::findOrFail($id);
        $updated = $service->updateSchedule($doctor, $request->validated());

        return new DoctorResource($updated);
    }
}
