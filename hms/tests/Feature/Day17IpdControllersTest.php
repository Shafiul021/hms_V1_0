<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\NursingNote;
use Hms\Core\Enums\BedStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class Day17IpdControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User    $admin;
    protected User    $doctorUser;
    protected User    $nurseUser;
    protected User    $patientUser;
    protected Patient $patient;
    protected Doctor  $doctor;
    protected Ward    $ward;
    protected Bed     $bed;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'doctor', 'nurse', 'patient', 'receptionist'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $this->nurseUser = User::factory()->create();
        $this->nurseUser->assignRole('nurse');

        $this->patientUser = User::factory()->create();
        $this->patientUser->assignRole('patient');

        $this->patient = Patient::create([
            'user_id'      => $this->patientUser->id,
            'patient_code' => 'HMS-2026-00001',
            'dob'          => '1990-01-01',
            'blood_type'   => 'A+',
            'gender'       => 'male',
        ]);

        $this->doctor = Doctor::create([
            'user_id'        => $this->doctorUser->id,
            'specialization' => 'Internal Medicine',
            'qualification'  => 'MD',
            'fee'            => 150.00,
        ]);

        $this->ward = Ward::create([
            'name'       => 'General Ward A',
            'type'       => 'general',
            'capacity'   => 10,
            'daily_rate' => 500.00,
        ]);

        $this->bed = Bed::create([
            'ward_id'    => $this->ward->id,
            'bed_number' => 'G-01',
            'status'     => BedStatus::Available,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // WARD TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_nurse_can_list_wards(): void
    {
        $this->actingAs($this->nurseUser)
            ->getJson('/api/wards')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'General Ward A')
            ->assertJsonPath('data.0.type', 'general');
    }

    public function test_patient_cannot_list_wards(): void
    {
        $this->actingAs($this->patientUser)
            ->getJson('/api/wards')
            ->assertStatus(403);
    }

    public function test_nurse_can_list_beds_in_ward(): void
    {
        // Add a second bed
        Bed::create([
            'ward_id'    => $this->ward->id,
            'bed_number' => 'G-02',
            'status'     => BedStatus::Available,
        ]);

        $this->actingAs($this->nurseUser)
            ->getJson("/api/wards/{$this->ward->id}/beds")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'available');
    }

    // ────────────────────────────────────────────────────────────────────────
    // ADMISSION TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_doctor_can_admit_patient_to_available_bed(): void
    {
        $payload = [
            'patient_id' => $this->patient->id,
            'bed_id'     => $this->bed->id,
            'doctor_id'  => $this->doctor->id,
            'reason'     => 'Acute chest pain observation',
        ];

        $response = $this->actingAs($this->doctorUser)
            ->postJson('/api/admissions', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.patient_id', $this->patient->id);

        // Bed status should now be Occupied
        $this->assertDatabaseHas('beds', [
            'id'     => $this->bed->id,
            'status' => BedStatus::Occupied->value,
        ]);

        $this->assertDatabaseHas('admissions', [
            'patient_id' => $this->patient->id,
            'bed_id'     => $this->bed->id,
            'doctor_id'  => $this->doctor->id,
        ]);
    }

    public function test_cannot_admit_to_occupied_bed(): void
    {
        // Mark bed as occupied
        $this->bed->update(['status' => BedStatus::Occupied]);

        $this->actingAs($this->doctorUser)
            ->postJson('/api/admissions', [
                'patient_id' => $this->patient->id,
                'bed_id'     => $this->bed->id,
                'doctor_id'  => $this->doctor->id,
                'reason'     => 'Should fail',
            ])
            ->assertStatus(422);
    }

    public function test_admission_requires_all_fields(): void
    {
        $this->actingAs($this->doctorUser)
            ->postJson('/api/admissions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id', 'bed_id', 'doctor_id', 'reason']);
    }

    public function test_patient_role_cannot_create_admission(): void
    {
        $this->actingAs($this->patientUser)
            ->postJson('/api/admissions', [
                'patient_id' => $this->patient->id,
                'bed_id'     => $this->bed->id,
                'doctor_id'  => $this->doctor->id,
                'reason'     => 'Unauthorized',
            ])
            ->assertStatus(403);
    }

    public function test_doctor_can_discharge_patient_and_bed_becomes_available(): void
    {
        // First admit
        $admission = Admission::create([
            'patient_id'  => $this->patient->id,
            'bed_id'      => $this->bed->id,
            'doctor_id'   => $this->doctor->id,
            'reason'      => 'Observation',
            'admitted_at' => now()->subDays(2),
        ]);
        $this->bed->update(['status' => BedStatus::Occupied]);

        // Discharge
        $this->actingAs($this->doctorUser)
            ->patchJson("/api/admissions/{$admission->id}/discharge")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Bed should be Available again
        $this->assertDatabaseHas('beds', [
            'id'     => $this->bed->id,
            'status' => BedStatus::Available->value,
        ]);

        // Admission should have discharged_at set
        $this->assertNotNull(Admission::find($admission->id)->discharged_at);
    }

    public function test_cannot_discharge_already_discharged_patient(): void
    {
        $admission = Admission::create([
            'patient_id'    => $this->patient->id,
            'bed_id'        => $this->bed->id,
            'doctor_id'     => $this->doctor->id,
            'reason'        => 'Already discharged',
            'admitted_at'   => now()->subDays(3),
            'discharged_at' => now()->subDay(),
        ]);

        $this->actingAs($this->doctorUser)
            ->patchJson("/api/admissions/{$admission->id}/discharge")
            ->assertStatus(422);
    }

    // ────────────────────────────────────────────────────────────────────────
    // NURSING NOTES TESTS
    // ────────────────────────────────────────────────────────────────────────

    public function test_nurse_can_add_nursing_note(): void
    {
        $admission = Admission::create([
            'patient_id'  => $this->patient->id,
            'bed_id'      => $this->bed->id,
            'doctor_id'   => $this->doctor->id,
            'reason'      => 'Monitoring',
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->nurseUser)
            ->postJson("/api/admissions/{$admission->id}/notes", [
                'note' => 'Vitals stable. BP 120/80, HR 72.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.note', 'Vitals stable. BP 120/80, HR 72.')
            ->assertJsonPath('data.admission_id', $admission->id);

        $this->assertDatabaseHas('nursing_notes', [
            'admission_id' => $admission->id,
            'nurse_id'     => $this->nurseUser->id,
            'note'         => 'Vitals stable. BP 120/80, HR 72.',
        ]);
    }

    public function test_note_requires_content(): void
    {
        $admission = Admission::create([
            'patient_id'  => $this->patient->id,
            'bed_id'      => $this->bed->id,
            'doctor_id'   => $this->doctor->id,
            'reason'      => 'Monitoring',
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->nurseUser)
            ->postJson("/api/admissions/{$admission->id}/notes", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['note']);
    }

    public function test_can_list_nursing_notes_chronologically(): void
    {
        $admission = Admission::create([
            'patient_id'  => $this->patient->id,
            'bed_id'      => $this->bed->id,
            'doctor_id'   => $this->doctor->id,
            'reason'      => 'Monitoring',
            'admitted_at' => now(),
        ]);

        NursingNote::create([
            'admission_id' => $admission->id,
            'nurse_id'     => $this->nurseUser->id,
            'note'         => 'First note',
            'recorded_at'  => now()->subHour(),
        ]);

        NursingNote::create([
            'admission_id' => $admission->id,
            'nurse_id'     => $this->nurseUser->id,
            'note'         => 'Second note',
            'recorded_at'  => now(),
        ]);

        $this->actingAs($this->doctorUser)
            ->getJson("/api/admissions/{$admission->id}/notes")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.note', 'First note')
            ->assertJsonPath('data.1.note', 'Second note');
    }

    public function test_patient_cannot_add_nursing_note(): void
    {
        $admission = Admission::create([
            'patient_id'  => $this->patient->id,
            'bed_id'      => $this->bed->id,
            'doctor_id'   => $this->doctor->id,
            'reason'      => 'Monitoring',
            'admitted_at' => now(),
        ]);

        $this->actingAs($this->patientUser)
            ->postJson("/api/admissions/{$admission->id}/notes", [
                'note' => 'Unauthorized note',
            ])
            ->assertStatus(403);
    }
}
