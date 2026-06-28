<?php

namespace App\Models;

use Hms\Core\Enums\BillStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'status',
        'total_amount',
        'paid_amount',
        'due_date',
        'issued_at',
    ];

    protected $casts = [
        'status' => BillStatus::class,
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items()
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
