<?php

namespace App\Listeners;

use App\Enums\LogStatus;
use App\Events\UserLoggedIn;
use App\Models\LoginLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\Activity;

class UserLoginListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;

        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => LogStatus::Success,
            'message' => 'Login successful.',
        ]);

        Activity::causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->event('login')
            ->log('User logged in successfully.');
    }
}
