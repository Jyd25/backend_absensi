<?php

namespace App\Listeners;

use App\Events\DashboardBroadcast;
use App\Events\DashboardUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DashboardListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DashboardUpdated $event): void
    {
        try {
            broadcast(new DashboardBroadcast(['timestamp' => now()->toISOString()]));
        } catch (\Throwable $e) {
            Log::error('DashboardListener gagal: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}