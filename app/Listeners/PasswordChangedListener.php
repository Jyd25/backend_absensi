<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\Activity;

class PasswordChangedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PasswordChanged $event): void
    {
        $user = $event->user;

        Activity::causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->event('password_changed')
            ->log('User changed password successfully.');
    }
}
