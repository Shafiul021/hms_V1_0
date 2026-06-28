<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_request_id',
        'technician_id',
        'result_file',
        'notes',
        'result_at',
        'is_abnormal',
    ];

    protected $casts = [
        'result_at' => 'datetime',
        'is_abnormal' => 'boolean',
    ];

    public function labRequest()
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
