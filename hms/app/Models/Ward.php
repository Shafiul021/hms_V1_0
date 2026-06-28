<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'capacity',
        'daily_rate',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'capacity' => 'integer',
    ];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function admissions()
    {
        return $this->hasManyThrough(Admission::class, Bed::class);
    }
}
