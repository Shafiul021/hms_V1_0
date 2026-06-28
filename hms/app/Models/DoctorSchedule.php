<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'doctor_schedule_id');
    }
}
