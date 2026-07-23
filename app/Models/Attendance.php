<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FaceStatus;
use App\Enums\LocationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'location_id',
        'schedule_id',
        'attendance_type',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'distance',
        'face_score',
        'location_status',
        'face_status',
        'attendance_status',
        'device',
        'ip_address',
        'remarks',
        'photo_path',
        'photo_data',
        'checkin_photo_data',
        'checkout_photo_data',
        'status_checkout',
        'address',
        'checkout_address',
    ];

    protected $casts = [
        'attendance_type' => AttendanceType::class,
        'location_status' => LocationStatus::class,
        'face_status' => FaceStatus::class,
        'attendance_status' => AttendanceStatus::class,
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance' => 'decimal:2',
        'face_score' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'location_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'schedule_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AttendanceHistory::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(AttendanceProcess::class);
    }

    public function getWorkDurationAttribute(): ?string
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);
        $minutes = $checkIn->diffInMinutes($checkOut);
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%dh %dm', $hours, $mins);
    }

    public function scopeLatest($query)
    {
        return $query->latest('check_in_time');
    }

    public function scopeByEmployee($query, $empId)
    {
        return $query->where('employee_id', $empId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('check_in_time', $date);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('attendance_status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', today());
    }
}
