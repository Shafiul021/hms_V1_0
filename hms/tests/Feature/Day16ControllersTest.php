<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Medicine;
use Hms\Core\Enums\AppointmentStatus;
use Hms\Core\Enums\LabRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class Day16ControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User       $admin;
    protected User       $doctorUser;
    protected User       $nurseUser;
    protected User       $patientUser;
    protected Patient    $patient;
    protected Doctor     $doctor;
    protected Appointment $appointment;
    protected LabTest    $labTest;
    protected Medicine   $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        foreach (['admin', 'doctor', 'nurse', 'patient', 'receptionist'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $this->nurseUser = User::factory()->create();
        $this->nurseUser->assignRole('nurse');

        $this->patientUser = User::factory()->create();
        $this->patientUser->assignRole('patient');

        // Create profiles
        $this->patient = Patient::create([
            'user_id'      => $this->patientUser->id,
            'patient_code' => 'HMS-2026-00001',
            'dob'          => '1990-01-01',
            'blood_type'   => 'A+',
            'gender'       => 'male',
        ]);

        $this->doctor = Doctor::create([
            'user_id'        => $this->doctorUser->id,
            'specialization' => 'General Medicine',
            'qualification'  => 'MD',
            'fee'            => 100.00,
        ]);

        // Grab an auto-seeded Monday schedule slot
        $schedule = DoctorSchedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();
        $slot = TimeSlot::where('doctor_schedule_id', $schedule->id)->firstOrFail();

        // Create appointment directly (bypass mail queue)
        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'slot_id'    => $slot->id,
            'date'       => '2026-06-29',
            'status'     => AppointmentStatus::Confirmed,
            'booked_by'  => $this->patientUser->id,
            'notes'      => 'General check-up',
        ]);

        // Reference data
        $this->labTest = LabTest::create([
            'name'             => 'Complete Blood Count',
            'code'             => 'CBC',
            'price'            => 50.00,
            'turnaround_hours' => 24,
        ]);

        $this->medicine = Medicine::create([
            'name'            => 'Paracetamol',
            'generic_name'    => 'Acetaminophen',
            'unit'            => 'tablet',
            'price'           => 5.00,
            'stock_threshold' => 10,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // DIAGNOSIS TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_doctor_can_create_diagnosis(): void
    {
        $payload = [
            'appointment_id' => $this->appointment->id,
            'icd_code'       => 'J06.9',
            'description'    => 'Upper Respiratory Tract Infection',
            'notes'          => 'Mild fever and cough',
        ];

        $response = $this->actingAs($this->doctorUser)
            ->postJson('/api/diagnoses', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.icd_code', 'J06.9')
            ->assertJsonPath('data.description', 'Upper Respiratory Tract Infection');

        $this->assertDatabaseHas('diagnoses', [
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'icd_code'       => 'J06.9',
        ]);
    }

    public function test_non_doctor_cannot_create_diagnosis(): void
    {
        $payload = [
            'appointment_id' => $this->appointment->id,
            'description'    => 'Some diagnosis',
        ];

        $this->actingAs($this->nurseUser)
            ->postJson('/api/diagnoses', $payload)
            ->assertStatus(403);

        $this->actingAs($this->patientUser)
            ->postJson('/api/diagnoses', $payload)
            ->assertStatus(403);
    }

    public function test_diagnosis_requires_appointment_id_and_description(): void
    {
        $this->actingAs($this->doctorUser)
            ->postJson('/api/diagnoses', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['appointment_id', 'description']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRESCRIPTION TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_doctor_can_create_prescription_with_items(): void
    {
        $payload = [
            'appointment_id' => $this->appointment->id,
            'notes'          => 'Take after meals',
            'items'          => [
                [
                    'medicine_id' => $this->medicine->id,
                    'dosage'      => '500mg',
                    'frequency'   => 'TID',
                    'duration'    => '5 days',
                ],
            ],
        ];

        $response = $this->actingAs($this->doctorUser)
            ->postJson('/api/prescriptions', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.notes', 'Take after meals');

        $prescriptionId = $response->json('data.id');

        $this->assertDatabaseHas('prescriptions', [
            'id'             => $prescriptionId,
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
        ]);

        $this->assertDatabaseHas('prescription_items', [
            'prescription_id' => $prescriptionId,
            'medicine_id'     => $this->medicine->id,
            'dosage'          => '500mg',
            'frequency'       => 'TID',
        ]);
    }

    public function test_prescription_requires_at_least_one_item(): void
    {
        $this->actingAs($this->doctorUser)
            ->postJson('/api/prescriptions', [
                'appointment_id' => $this->appointment->id,
                'items'          => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_view_prescription(): void
    {
        $prescription = Prescription::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'notes'          => 'Sample prescription',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id'     => $this->medicine->id,
            'dosage'          => '250mg',
            'frequency'       => 'BD',
            'duration'        => '7 days',
        ]);

        $this->actingAs($this->doctorUser)
            ->getJson("/api/prescriptions/{$prescription->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $prescription->id)
            ->assertJsonCount(1, 'data.items');
    }

    // ────────────────────────────────────────────────────────────────────────
    // LAB REQUEST TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_doctor_can_create_lab_request(): void
    {
        $payload = [
            'appointment_id' => $this->appointment->id,
            'test_id'        => $this->labTest->id,
        ];

        $response = $this->actingAs($this->doctorUser)
            ->postJson('/api/lab-requests', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.status', LabRequestStatus::Requested->value);

        $this->assertDatabaseHas('lab_requests', [
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Requested->value,
        ]);
    }

    public function test_lab_request_requires_valid_test_id(): void
    {
        $this->actingAs($this->doctorUser)
            ->postJson('/api/lab-requests', [
                'appointment_id' => $this->appointment->id,
                'test_id'        => 9999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['test_id']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // LAB RESULT TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_nurse_can_upload_lab_result(): void
    {
        Storage::fake('private');

        // First create a lab request
        $labRequest = LabRequest::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Requested,
            'requested_at'   => now(),
        ]);

        $fakeFile = UploadedFile::fake()->create('result.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->nurseUser)
            ->call(
                'PATCH',
                "/api/lab-results/{$labRequest->id}",
                ['notes' => 'All values within normal range', 'is_abnormal' => false],
                [],
                ['result_file' => $fakeFile],
                ['Content-Type' => 'multipart/form-data']
            )
            ->assertStatus(201)
            ->assertJsonPath('data.is_abnormal', false);

        // Lab request status should be updated to completed
        $this->assertDatabaseHas('lab_requests', [
            'id'     => $labRequest->id,
            'status' => LabRequestStatus::Completed->value,
        ]);

        // Lab result should exist
        $this->assertDatabaseHas('lab_results', [
            'lab_request_id' => $labRequest->id,
            'technician_id'  => $this->nurseUser->id,
        ]);
    }

    public function test_can_view_lab_result(): void
    {
        $labRequest = LabRequest::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Completed,
            'requested_at'   => now(),
        ]);

        $result = LabResult::create([
            'lab_request_id' => $labRequest->id,
            'technician_id'  => $this->nurseUser->id,
            'result_file'    => 'lab-results/1/test-result.pdf',
            'notes'          => 'Normal',
            'is_abnormal'    => false,
            'result_at'      => now(),
        ]);

        $this->actingAs($this->doctorUser)
            ->getJson("/api/lab-results/{$result->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $result->id)
            ->assertJsonPath('data.notes', 'Normal');
    }

    public function test_patient_cannot_upload_lab_result(): void
    {
        $labRequest = LabRequest::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Requested,
            'requested_at'   => now(),
        ]);

        $this->actingAs($this->patientUser)
            ->patchJson("/api/lab-results/{$labRequest->id}", ['notes' => 'Unauthorized'])
            ->assertStatus(403);
    }

    public function test_download_returns_404_when_no_file(): void
    {
        Storage::fake('private');

        $labRequest = LabRequest::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Completed,
            'requested_at'   => now(),
        ]);

        // Result with a file path that does NOT physically exist in the fake storage
        $result = LabResult::create([
            'lab_request_id' => $labRequest->id,
            'technician_id'  => $this->nurseUser->id,
            'result_file'    => 'lab-results/1/non-existent.pdf',
            'notes'          => 'Verbal result only',
            'is_abnormal'    => false,
            'result_at'      => now(),
        ]);

        // Signed URL for download
        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'lab-results.download',
            now()->addMinutes(30),
            ['id' => $result->id]
        );

        // Extract path + query string (host stripped for test HTTP client)
        $path  = parse_url($signedUrl, PHP_URL_PATH);
        $query = parse_url($signedUrl, PHP_URL_QUERY);

        $this->actingAs($this->doctorUser)
            ->getJson($path . '?' . $query)
            ->assertStatus(404);
    }
}
