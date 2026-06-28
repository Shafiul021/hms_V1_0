<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id',
        'batch_no',
        'quantity',
        'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
