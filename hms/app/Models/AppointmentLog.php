<?php

namespace App\Models;

use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'appointment_id',
        'old_status',
        'new_status',
        'changed_by',
    ];

    protected $casts = [
        'old_status' => AppointmentStatus::class,
        'new_status' => AppointmentStatus::class,
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
