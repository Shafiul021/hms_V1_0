<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Appointment::with(['patient.user', 'doctor.user', 'slot']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->query('doctor_id'));
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        $appointments = $query->paginate($request->query('per_page', 15));

        return AppointmentResource::collection($appointments);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(StoreAppointmentRequest $request, AppointmentService $service): AppointmentResource
    {
        $appointment = $service->book($request->validated(), auth()->user());

        return new AppointmentResource($appointment);
    }

    /**
     * Display the specified appointment.
     */
    public function show(int $id): AppointmentResource
    {
        $appointment = Appointment::with(['patient.user', 'doctor.user', 'slot', 'logs.changedBy'])->findOrFail($id);

        return new AppointmentResource($appointment);
    }

    /**
     * Update status on the specified appointment.
     */
    public function updateStatus(UpdateAppointmentStatusRequest $request, int $id, AppointmentService $service): AppointmentResource
    {
        $appointment = Appointment::findOrFail($id);
        $newStatus = AppointmentStatus::from($request->status);
        $updated = $service->updateStatus($appointment, $newStatus, auth()->user());

        return new AppointmentResource($updated);
    }

    /**
     * Remove the specified appointment (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json(['message' => 'Appointment cancelled and deleted successfully.']);
    }
}
