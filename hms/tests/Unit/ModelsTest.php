<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Allergy;
use App\Models\EmergencyContact;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Models\Diagnosis;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Admission;
use App\Models\NursingNote;
use App\Models\LabTest;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Dispensing;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;

class ModelsTest extends TestCase
{
    public function test_all_models_can_be_instantiated()
    {
        $this->assertInstanceOf(User::class, new User());
        $this->assertInstanceOf(Patient::class, new Patient());
        $this->assertInstanceOf(Doctor::class, new Doctor());
        $this->assertInstanceOf(Allergy::class, new Allergy());
        $this->assertInstanceOf(EmergencyContact::class, new EmergencyContact());
        $this->assertInstanceOf(DoctorSchedule::class, new DoctorSchedule());
        $this->assertInstanceOf(TimeSlot::class, new TimeSlot());
        $this->assertInstanceOf(Appointment::class, new Appointment());
        $this->assertInstanceOf(AppointmentLog::class, new AppointmentLog());
        $this->assertInstanceOf(Diagnosis::class, new Diagnosis());
        $this->assertInstanceOf(Prescription::class, new Prescription());
        $this->assertInstanceOf(PrescriptionItem::class, new PrescriptionItem());
        $this->assertInstanceOf(Ward::class, new Ward());
        $this->assertInstanceOf(Bed::class, new Bed());
        $this->assertInstanceOf(Admission::class, new Admission());
        $this->assertInstanceOf(NursingNote::class, new NursingNote());
        $this->assertInstanceOf(LabTest::class, new LabTest());
        $this->assertInstanceOf(LabRequest::class, new LabRequest());
        $this->assertInstanceOf(LabResult::class, new LabResult());
        $this->assertInstanceOf(Medicine::class, new Medicine());
        $this->assertInstanceOf(MedicineBatch::class, new MedicineBatch());
        $this->assertInstanceOf(Dispensing::class, new Dispensing());
        $this->assertInstanceOf(Bill::class, new Bill());
        $this->assertInstanceOf(BillItem::class, new BillItem());
        $this->assertInstanceOf(Payment::class, new Payment());
    }

    public function test_relationships_are_defined()
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->patient());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->doctor());

        $patient = new Patient();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $patient->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $patient->allergies());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $patient->emergencyContacts());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $patient->appointments());

        $doctor = new Doctor();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $doctor->user());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $doctor->schedules());
    }
}
