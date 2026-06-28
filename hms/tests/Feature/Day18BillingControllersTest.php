<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\LabTest;
use App\Models\LabRequest;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use Hms\Core\Enums\AppointmentStatus;
use Hms\Core\Enums\BedStatus;
use Hms\Core\Enums\BillStatus;
use Hms\Core\Enums\LabRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class Day18BillingControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionist;
    protected User $doctorUser;
    protected User $patientUser;
    protected User $otherPatientUser;
    protected Patient $patient;
    protected Patient $otherPatient;
    protected Doctor $doctor;
    protected Appointment $appointment;
    protected LabTest $labTest;
    protected Medicine $medicine;
    protected Ward $ward;
    protected Bed $bed;

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

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $this->doctorUser = User::factory()->create();
        $this->doctorUser->assignRole('doctor');

        $this->patientUser = User::factory()->create();
        $this->patientUser->assignRole('patient');

        $this->otherPatientUser = User::factory()->create();
        $this->otherPatientUser->assignRole('patient');

        // Create profiles
        $this->patient = Patient::create([
            'user_id'      => $this->patientUser->id,
            'patient_code' => 'HMS-2026-00001',
            'dob'          => '1990-01-01',
            'blood_type'   => 'A+',
            'gender'       => 'male',
        ]);

        $this->otherPatient = Patient::create([
            'user_id'      => $this->otherPatientUser->id,
            'patient_code' => 'HMS-2026-00002',
            'dob'          => '1992-02-02',
            'blood_type'   => 'O+',
            'gender'       => 'female',
        ]);

        $this->doctor = Doctor::create([
            'user_id'        => $this->doctorUser->id,
            'specialization' => 'General Medicine',
            'qualification'  => 'MD',
            'fee'            => 120.00,
        ]);

        // Setup appointment slot
        $schedule = DoctorSchedule::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', 1)
            ->firstOrFail();
        $slot = TimeSlot::where('doctor_schedule_id', $schedule->id)->firstOrFail();

        $this->appointment = Appointment::create([
            'patient_id' => $this->patient->id,
            'doctor_id'  => $this->doctor->id,
            'slot_id'    => $slot->id,
            'date'       => '2026-06-29',
            'status'     => AppointmentStatus::Confirmed,
            'booked_by'  => $this->patientUser->id,
            'notes'      => 'Regular Checkup',
        ]);

        // Setup reference objects
        $this->labTest = LabTest::create([
            'name'             => 'Lipid Profile',
            'code'             => 'LIPID',
            'price'            => 60.00,
            'turnaround_hours' => 12,
        ]);

        $this->medicine = Medicine::create([
            'name'            => 'Amoxicillin',
            'generic_name'    => 'Amoxicillin',
            'unit'            => 'capsule',
            'price'           => 2.50,
            'stock_threshold' => 20,
        ]);

        $this->ward = Ward::create([
            'name'       => 'VIP Ward',
            'type'       => 'private',
            'capacity'   => 5,
            'daily_rate' => 300.00,
        ]);

        $this->bed = Bed::create([
            'ward_id'    => $this->ward->id,
            'bed_number' => 'VIP-1',
            'status'     => BedStatus::Available,
        ]);
    }

    public function test_admin_and_receptionist_can_generate_bill(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/bills/generate', [
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bills', [
            'appointment_id' => $this->appointment->id,
            'patient_id'     => $this->patient->id,
            'total_amount'   => 120.00, // consultation only
            'status'         => BillStatus::Issued->value,
        ]);
    }

    public function test_billing_aggregates_all_costs(): void
    {
        // 1. Lab Request
        LabRequest::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'test_id'        => $this->labTest->id,
            'status'         => LabRequestStatus::Requested,
            'requested_at'   => now(),
        ]);

        // 2. Prescription
        $prescription = Prescription::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id'      => $this->doctor->id,
            'patient_id'     => $this->patient->id,
            'notes'          => 'Take regularly',
        ]);
        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medicine_id'     => $this->medicine->id,
            'dosage'          => '500mg',
            'frequency'       => 'TID', // 3 times per day
            'duration'        => '6 days', // 6 * 3 = 18 total quantity
        ]);

        // 3. Admission (3 days)
        Admission::create([
            'patient_id'   => $this->patient->id,
            'bed_id'       => $this->bed->id,
            'doctor_id'    => $this->doctor->id,
            'admitted_at'  => Carbon::now()->subDays(3),
            'discharged_at'=> Carbon::now(),
            'reason'       => 'Observation',
        ]);

        $response = $this->actingAs($this->receptionist)
            ->postJson('/api/bills/generate', [
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertStatus(201);
        
        $expectedTotal = 120.00 + 60.00 + 45.00 + 900.00;
        
        $response->assertJsonPath('data.total_amount', number_format($expectedTotal, 2, '.', ''));

        $this->assertDatabaseHas('bills', [
            'appointment_id' => $this->appointment->id,
            'total_amount'   => $expectedTotal,
        ]);

        $this->assertDatabaseHas('bill_items', [
            'item_type'   => 'consultation',
            'total'       => 120.00,
        ]);
        $this->assertDatabaseHas('bill_items', [
            'item_type'   => 'lab',
            'total'       => 60.00,
        ]);
        $this->assertDatabaseHas('bill_items', [
            'item_type'   => 'medicine',
            'quantity'    => 18,
            'total'       => 45.00,
        ]);
        $this->assertDatabaseHas('bill_items', [
            'item_type'   => 'bed',
            'quantity'    => 3,
            'total'       => 900.00,
        ]);
    }

    public function test_cannot_generate_duplicate_bill_for_same_appointment(): void
    {
        // Generate first bill
        $this->actingAs($this->admin)
            ->postJson('/api/bills/generate', [
                'appointment_id' => $this->appointment->id,
            ])
            ->assertStatus(201);

        // Try duplicate
        $this->actingAs($this->admin)
            ->postJson('/api/bills/generate', [
                'appointment_id' => $this->appointment->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A bill has already been generated for this appointment.');
    }

    public function test_patient_cannot_generate_bill(): void
    {
        $this->actingAs($this->patientUser)
            ->postJson('/api/bills/generate', [
                'appointment_id' => $this->appointment->id,
            ])
            ->assertStatus(403);
    }

    public function test_patient_can_view_own_bill_but_not_others(): void
    {
        $bill = Bill::create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'status'         => BillStatus::Issued,
            'total_amount'   => 120.00,
            'paid_amount'    => 0.00,
            'due_date'       => now()->addDays(7),
            'issued_at'      => now(),
        ]);

        // Own bill
        $this->actingAs($this->patientUser)
            ->getJson("/api/bills/{$bill->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $bill->id);

        // Other patient's bill
        $this->actingAs($this->otherPatientUser)
            ->getJson("/api/bills/{$bill->id}")
            ->assertStatus(403);
    }

    public function test_receptionist_can_record_payment(): void
    {
        $bill = Bill::create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'status'         => BillStatus::Issued,
            'total_amount'   => 120.00,
            'paid_amount'    => 0.00,
            'due_date'       => now()->addDays(7),
            'issued_at'      => now(),
        ]);

        // Partial payment
        $this->actingAs($this->receptionist)
            ->postJson('/api/payments', [
                'bill_id'      => $bill->id,
                'amount'       => 50.00,
                'method'       => 'cash',
                'reference_no' => 'REF12345',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('bills', [
            'id'          => $bill->id,
            'paid_amount' => 50.00,
            'status'      => BillStatus::Partial->value,
        ]);

        // Full payment completion
        $this->actingAs($this->receptionist)
            ->postJson('/api/payments', [
                'bill_id'      => $bill->id,
                'amount'       => 70.00,
                'method'       => 'online',
                'reference_no' => 'REF67890',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('bills', [
            'id'          => $bill->id,
            'paid_amount' => 120.00,
            'status'      => BillStatus::Paid->value,
        ]);
    }

    public function test_can_download_invoice_pdf(): void
    {
        $bill = Bill::create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => $this->appointment->id,
            'status'         => BillStatus::Issued,
            'total_amount'   => 120.00,
            'paid_amount'    => 0.00,
            'due_date'       => now()->addDays(7),
            'issued_at'      => now(),
        ]);

        $response = $this->actingAs($this->patientUser)
            ->get("/api/bills/{$bill->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
