<?php

namespace App\Providers;

use App\Events\AttendanceCheckedOut;
use App\Events\AttendanceCreated;
use App\Events\DashboardUpdated;
use App\Events\LoginFailed;
use App\Events\NotificationCreated;
use App\Events\PasswordChanged;
use App\Events\UserLoggedIn;
use App\Events\UserLoggedOut;
use App\Listeners\AttendanceListener;
use App\Listeners\DashboardListener;
use App\Listeners\LoginFailedListener;
use App\Listeners\NotificationListener;
use App\Listeners\PasswordChangedListener;
use App\Listeners\UserLoginListener;
use App\Listeners\UserLogoutListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserLoggedIn::class => [
            UserLoginListener::class,
        ],
        UserLoggedOut::class => [
            UserLogoutListener::class,
        ],
        PasswordChanged::class => [
            PasswordChangedListener::class,
        ],
        LoginFailed::class => [
            LoginFailedListener::class,
        ],
        AttendanceCreated::class => [
            AttendanceListener::class,
        ],
        NotificationCreated::class => [
            NotificationListener::class,
        ],
        DashboardUpdated::class => [
            DashboardListener::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
