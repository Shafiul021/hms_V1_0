<?php

namespace App\Models;

use Hms\Core\Enums\LabRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'test_id',
        'status',
        'requested_at',
    ];

    protected $casts = [
        'status' => LabRequestStatus::class,
        'requested_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function test()
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function result()
    {
        return $this->hasOne(LabResult::class);
    }
}
