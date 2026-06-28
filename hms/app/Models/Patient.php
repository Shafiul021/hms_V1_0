<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'patient_code',
        'dob',
        'blood_type',
        'gender',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allergies()
    {
        return $this->hasMany(Allergy::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
