<?php

namespace App\Models;

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
