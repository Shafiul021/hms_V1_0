<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_schedule_id',
        'start_time',
        'end_time',
        'is_blocked',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(DoctorSchedule::class, 'doctor_schedule_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'slot_id');
    }
}
