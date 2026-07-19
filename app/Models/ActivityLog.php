<?php

namespace App\Models;

use Spatie\ActivityLog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
    protected $table = 'activity_logs';
}
