<?php

namespace App\Listeners;

use App\Enums\LogStatus;
use App\Events\LoginFailed;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LoginFailedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LoginFailed $event): void
    {
        $user = User::where('email', $event->email)->first();

        LoginLog::create([
            'user_id' => $user?->id,
            'email' => $event->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => LogStatus::Failed,
            'message' => 'Login failed.',
        ]);
    }
}
