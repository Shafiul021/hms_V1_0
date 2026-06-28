<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'bed_id',
        'doctor_id',
        'admitted_at',
        'discharged_at',
        'reason',
        'notes',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function nursingNotes()
    {
        return $this->hasMany(NursingNote::class);
    }
}
