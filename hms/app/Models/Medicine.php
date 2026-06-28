<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'generic_name',
        'unit',
        'price',
        'stock_threshold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_threshold' => 'integer',
    ];

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
