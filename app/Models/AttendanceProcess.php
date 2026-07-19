<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'step',
        'status',
        'description',
        'processed_at',
    ];

    protected $casts = [
        'status' => ProcessStatus::class,
        'processed_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
