<?php

namespace App\Listeners;

use App\Events\DashboardUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DashboardListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DashboardUpdated $event): void
    {
        broadcast()->event('dashboard', [
            'type' => 'dashboard_updated',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
