<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PrescriptionResource;
use App\Http\Resources\LabResultResource;
use App\Http\Resources\BillResource;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\LabResult;
use App\Models\Bill;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Patient::with(['user', 'allergies', 'emergencyContacts']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $patients = $query->paginate($request->query('per_page', 15));

        return PatientResource::collection($patients);
    }

    /**
     * Store a newly created patient.
     */
    public function store(StorePatientRequest $request, PatientService $service): PatientResource
    {
        $patient = $service->create($request->validated());

        return new PatientResource($patient);
    }

    /**
     * Display the specified patient.
     */
    public function show(int $id): PatientResource
    {
        $patient = Patient::with(['user', 'allergies', 'emergencyContacts'])->findOrFail($id);

        return new PatientResource($patient);
    }

    /**
     * Update the specified patient.
     */
    public function update(UpdatePatientRequest $request, int $id, PatientService $service): PatientResource
    {
        $patient = Patient::findOrFail($id);
        $updated = $service->update($patient, $request->validated());

        return new PatientResource($updated);
    }

    /**
     * Remove the specified patient (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully.']);
    }

    /**
     * Display the patient's full medical history log.
     */
    public function history(int $id, PatientService $service): JsonResponse
    {
        $patient = Patient::findOrFail($id);

        return response()->json($service->getMedicalHistory($patient));
    }

    /**
     * Display all prescriptions for the patient.
     */
    public function prescriptions(int $id): AnonymousResourceCollection
    {
        $prescriptions = Prescription::with(['doctor.user', 'items.medicine', 'appointment'])
            ->where('patient_id', $id)
            ->get();

        return PrescriptionResource::collection($prescriptions);
    }

    /**
     * Display all lab results for the patient.
     */
    public function labResults(int $id): AnonymousResourceCollection
    {
        $labResults = LabResult::with(['labRequest.test', 'labRequest.doctor.user'])
            ->whereHas('labRequest', function ($q) use ($id) {
                $q->where('patient_id', $id);
            })
            ->get();

        return LabResultResource::collection($labResults);
    }

    /**
     * Display all bills for the patient.
     */
    public function bills(int $id): AnonymousResourceCollection
    {
        $bills = Bill::with(['appointment', 'payments', 'items'])
            ->where('patient_id', $id)
            ->get();

        return BillResource::collection($bills);
    }
}
