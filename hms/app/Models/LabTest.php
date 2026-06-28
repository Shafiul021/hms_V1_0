<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price',
        'turnaround_hours',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'turnaround_hours' => 'integer',
    ];

    public function requests()
    {
        return $this->hasMany(LabRequest::class, 'test_id');
    }
}
