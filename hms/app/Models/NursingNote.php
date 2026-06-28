<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NursingNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'nurse_id',
        'note',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }
}
