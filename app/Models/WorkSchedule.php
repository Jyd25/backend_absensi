<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'tolerance_minutes',
        'working_days',
        'saturday_start_time',
        'saturday_end_time',
        'is_active',
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'saturday_start_time' => 'datetime:H:i',
        'saturday_end_time' => 'datetime:H:i',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'schedule_id');
    }

    public function getStartTimeFormattedAttribute(): string
    {
        return $this->start_time ? $this->start_time->format('H:i') : '';
    }

    public function getEndTimeFormattedAttribute(): string
    {
        return $this->end_time ? $this->end_time->format('H:i') : '';
    }

    /**
     * Jam masuk efektif pada tanggal tertentu (Sabtu memakai jadwal Sabtu).
     */
    public function startTimeFor(Carbon $date): ?Carbon
    {
        $start = $date->isSaturday() && $this->saturday_start_time
            ? $this->saturday_start_time
            : $this->start_time;

        return $start ? Carbon::parse($start) : null;
    }

    /**
     * Jam pulang efektif pada tanggal tertentu (Sabtu memakai jadwal Sabtu).
     */
    public function endTimeFor(Carbon $date): ?Carbon
    {
        $end = $date->isSaturday() && $this->saturday_end_time
            ? $this->saturday_end_time
            : $this->end_time;

        return $end ? Carbon::parse($end) : null;
    }

    /**
     * Batas keterlambatan pada tanggal check-in, yaitu jam masuk jadwal
     * (toleransi tidak menggeser batas). Pegawai yang check-in antara pukul
     * 01:00 WIB sampai jam masuk selalu tercatat "Hadir", bukan "Terlambat".
     */
    public function lateThresholdFor(Carbon $checkIn): ?Carbon
    {
        $start = $this->startTimeFor($checkIn);

        return $start ? $checkIn->copy()->setTime($start->hour, $start->minute, 0) : null;
    }

    public function isLate(Carbon $checkIn): bool
    {
        $threshold = $this->lateThresholdFor($checkIn);

        return $threshold !== null && $checkIn->gt($threshold);
    }

    public function lateMinutes(Carbon $checkIn): int
    {
        $threshold = $this->lateThresholdFor($checkIn);

        return $threshold !== null && $checkIn->gt($threshold)
            ? (int) ceil($threshold->diffInMinutes($checkIn))
            : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
