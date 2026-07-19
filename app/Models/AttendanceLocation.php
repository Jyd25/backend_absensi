<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location_name',
        'latitude',
        'longitude',
        'radius',
        'address',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
