<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\Appointment;
use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class Day15ControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $doctorUser;
    protected User $patientUser;
    protected Patient $patient;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

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
    }

    public function test_patient_list_only_accessible_by_authorized_roles()
    {
        // Unauthenticated -> 401
        $this->getJson('/api/patients')->assertStatus(401);

        // Patient role -> 403
        $this->actingAs($this->patientUser)->getJson('/api/patients')->assertStatus(403);

        // Receptionist role -> 200
        $response = $this->actingAs($this->receptionist)->getJson('/api/patients')->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_can_manually_create_patient()
    {
        $patientData = [
            'name'               => 'Jane Doe',
            'email'              => 'jane@hms.com',
            'password'           => 'password123',
            'dob'                => '1995-05-15',
            'blood_type'         => 'O-',
            'gender'             => 'female',
            'allergies'          => [
                ['allergen' => 'Penicillin', 'severity' => 'high', 'notes' => 'Severe rash'],
            ],
            'emergency_contacts' => [
                ['name' => 'John Doe', 'relationship' => 'Spouse', 'phone' => '123456789'],
            ],
        ];

        $this->actingAs($this->receptionist)
            ->postJson('/api/patients', $patientData)
            ->assertStatus(201)
            ->assertJsonPath('data.blood_type', 'O-');

        $this->assertDatabaseHas('users', ['email' => 'jane@hms.com']);
        $this->assertDatabaseHas('allergies', ['allergen' => 'Penicillin', 'severity' => 'high']);
    }

    public function test_can_update_patient()
    {
        $updateData = [
            'name'       => 'Updated Patient Name',
            'blood_type' => 'AB+',
        ];

        $this->actingAs($this->receptionist)
            ->patchJson("/api/patients/{$this->patient->id}", $updateData)
            ->assertStatus(200)
            ->assertJsonPath('data.blood_type', 'AB+');

        $this->assertEquals('Updated Patient Name', $this->patient->user->fresh()->name);
    }

    public function test_can_view_patient_medical_history()
    {
        // Doctor can access
        $this->actingAs($this->doctorUser)
            ->getJson("/api/patients/{$this->patient->id}/history")
            ->assertStatus(200)
            ->assertJsonStructure(['patient', 'allergies', 'emergency_contacts', 'diagnoses', 'prescriptions']);
    }

    public function test_admin_can_create_doctor_and_auto_seed_schedule_via_observer()
    {
        $doctorData = [
            'name'           => 'Dr. Gregory House',
            'email'          => 'house@hms.com',
            'password'       => 'differential',
            'specialization' => 'Diagnostic Medicine',
            'qualification'  => 'MD',
            'fee'            => 300.00,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/doctors', $doctorData)
            ->assertStatus(201);

        $newDoctorId = $response->json('data.id');

        // Verify DoctorSchedule and TimeSlots are seeded (Mon-Fri)
        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_id'   => $newDoctorId,
            'day_of_week' => 1, // Mon
        ]);

        $this->assertDatabaseHas('time_slots', [
            'start_time' => '09:00:00',
            'end_time'   => '09:30:00',
        ]);
    }

    public function test_can_query_doctor_available_slots()
    {
        // Retrieve Monday schedule (seeded via observer during setup)
        $schedule = DoctorSchedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();

        $slot = TimeSlot::where('doctor_schedule_id', $schedule->id)->firstOrFail();

        // Query slots on a Monday (e.g. 2026-06-29)
        // Since observer seeded 16 slots, we expect 16 available slots.
        $this->actingAs($this->patientUser)
            ->getJson("/api/doctors/{$this->doctor->id}/slots?date=2026-06-29")
            ->assertStatus(200)
            ->assertJsonCount(16, 'data')
            ->assertJsonPath('data.0.id', $slot->id);
    }

    public function test_can_book_and_update_appointment_status()
    {
        $schedule = DoctorSchedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();
        $slot = TimeSlot::where('doctor_schedule_id', $schedule->id)->firstOrFail();

        // Mock mail dispatching
        \Illuminate\Support\Facades\Queue::fake();

        // 1. Book appointment
        $bookingResponse = $this->actingAs($this->patientUser)
            ->postJson('/api/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id'  => $this->doctor->id,
                'slot_id'    => $slot->id,
                'date'       => '2026-06-29',
                'notes'      => 'Heart palpitations checkup',
            ])
            ->assertStatus(201);

        $appointmentId = $bookingResponse->json('data.id');

        // 2. Transition status: Pending -> Confirmed (receptionist)
        $this->actingAs($this->receptionist)
            ->patchJson("/api/appointments/{$appointmentId}/status", [
                'status' => 'confirmed'
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }
}
