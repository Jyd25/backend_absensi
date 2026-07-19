<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs';

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
        'request_body',
        'response_body',
        'duration_ms',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
