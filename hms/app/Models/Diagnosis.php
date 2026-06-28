<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'icd_code',
        'description',
        'notes',
        'diagnosed_at',
    ];

    protected $casts = [
        'diagnosed_at' => 'datetime',
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
}
