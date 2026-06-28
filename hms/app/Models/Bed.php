<?php

namespace App\Models;

use Hms\Core\Enums\BedStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'bed_number',
        'status',
    ];

    protected $casts = [
        'status' => BedStatus::class,
    ];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }
}
