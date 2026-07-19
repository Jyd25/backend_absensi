<?php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\Activity;

class UserLogoutListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserLoggedOut $event): void
    {
        $user = $event->user;

        Activity::causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->event('logout')
            ->log('User logged out successfully.');
    }
}
