<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Services\AppointmentService;
use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Spatie\Permission\Models\Role;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $service;
    protected User $adminUser;
    protected User $doctorUser;
    protected User $receptionistUser;
    protected User $patientUser;
    protected User $patientUser2;
    protected Patient $patient;
    protected Patient $patient2;
    protected Doctor $doctor;
    protected TimeSlot $slot;
    protected TimeSlot $blockedSlot;
    protected TimeSlot $otherDoctorSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AppointmentService();

        // Create roles
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $this->receptionistUser = User::factory()->create();
        $this->receptionistUser->assignRole('receptionist');

        $this->patientUser = User::factory()->create();
        $this->patientUser->assignRole('patient');

        $this->patientUser2 = User::factory()->create();
        $this->patientUser2->assignRole('patient');

        // Create Patient and Doctor records
        $this->patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'patient_code' => 'HMS-2026-00001',
            'dob' => '1990-01-01',
            'blood_type' => 'O+',
            'gender' => 'male',
        ]);

        $this->patient2 = Patient::create([
            'user_id' => $this->patientUser2->id,
            'patient_code' => 'HMS-2026-00002',
            'dob' => '1992-05-10',
            'blood_type' => 'A-',
            'gender' => 'female',
        ]);

        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'specialization' => 'Cardiology',
            'qualification' => 'MD',
            'fee' => 150.00,
        ]);

        $schedule = DoctorSchedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();

        $this->slot = TimeSlot::where('doctor_schedule_id', $schedule->id)
            ->where('start_time', '09:00:00')
            ->firstOrFail();

        $this->blockedSlot = TimeSlot::where('doctor_schedule_id', $schedule->id)
            ->where('start_time', '09:30:00')
            ->firstOrFail();
        $this->blockedSlot->update(['is_blocked' => true]);

        // Other doctor slot
        $otherDoctorUser = User::factory()->create();
        $otherDoctorUser->assignRole('doctor');
        $otherDoctor = Doctor::create([
            'user_id' => $otherDoctorUser->id,
            'specialization' => 'Pediatrics',
            'qualification' => 'MBBS',
            'fee' => 100.00,
        ]);
        $otherSchedule = DoctorSchedule::where('doctor_id', $otherDoctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();
        $this->otherDoctorSlot = TimeSlot::where('doctor_schedule_id', $otherSchedule->id)
            ->where('start_time', '09:00:00')
            ->firstOrFail();

        // Mock mail dispatching so queue doesn't actually hit SMTP
        \Illuminate\Support\Facades\Queue::fake();
    }

    public function test_can_book_appointment_successfully()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
            'notes' => 'First checkup',
        ], $this->receptionistUser);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => AppointmentStatus::Pending->value,
            'booked_by' => $this->receptionistUser->id,
        ]);

        $this->assertDatabaseHas('appointment_logs', [
            'appointment_id' => $appointment->id,
            'old_status' => null,
            'new_status' => AppointmentStatus::Pending->value,
            'changed_by' => $this->receptionistUser->id,
        ]);
    }

    public function test_cannot_book_blocked_slot()
    {
        $this->expectException(ValidationException::class);

        $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->blockedSlot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);
    }

    public function test_cannot_book_slot_belonging_to_another_doctor()
    {
        $this->expectException(ValidationException::class);

        $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->otherDoctorSlot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);
    }

    public function test_cannot_book_already_booked_slot_on_same_date()
    {
        // First booking
        $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // Second booking should fail
        $this->expectException(ValidationException::class);

        $this->service->book([
            'patient_id' => $this->patient2->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->patientUser2);
    }

    public function test_status_transitions_by_allowed_roles()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // 1. Pending -> Confirmed (receptionist)
        $appointment = $this->service->updateStatus($appointment, AppointmentStatus::Confirmed, $this->receptionistUser);
        $this->assertEquals(AppointmentStatus::Confirmed, $appointment->status);

        // 2. Confirmed -> InProgress (doctor)
        $appointment = $this->service->updateStatus($appointment, AppointmentStatus::InProgress, $this->doctorUser);
        $this->assertEquals(AppointmentStatus::InProgress, $appointment->status);

        // 3. InProgress -> Completed (doctor)
        $appointment = $this->service->updateStatus($appointment, AppointmentStatus::Completed, $this->doctorUser);
        $this->assertEquals(AppointmentStatus::Completed, $appointment->status);
    }

    public function test_invalid_status_transition_throws_validation_exception()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // Pending -> InProgress (should fail)
        $this->expectException(ValidationException::class);
        $this->service->updateStatus($appointment, AppointmentStatus::InProgress, $this->doctorUser);
    }

    public function test_role_without_permission_throws_access_denied_exception()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // Patient trying to confirm (should fail)
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->updateStatus($appointment, AppointmentStatus::Confirmed, $this->patientUser);
    }

    public function test_patient_can_cancel_own_appointment()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // Patient cancels own
        $appointment = $this->service->updateStatus($appointment, AppointmentStatus::Cancelled, $this->patientUser);
        $this->assertEquals(AppointmentStatus::Cancelled, $appointment->status);
    }

    public function test_patient_cannot_cancel_others_appointment()
    {
        $appointment = $this->service->book([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'slot_id' => $this->slot->id,
            'date' => '2026-06-29',
        ], $this->receptionistUser);

        // Patient 2 cancels Patient 1's appointment (should fail)
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->updateStatus($appointment, AppointmentStatus::Cancelled, $this->patientUser2);
    }
}
