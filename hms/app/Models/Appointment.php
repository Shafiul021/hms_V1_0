<?php

namespace App\Models;

use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'slot_id',
        'date',
        'status',
        'booked_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => AppointmentStatus::class,
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function slot()
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id');
    }

    public function logs()
    {
        return $this->hasMany(AppointmentLog::class);
    }

    public function diagnosis()
    {
        return $this->hasOne(Diagnosis::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }
}
