<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\NeonDatabaseProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    NeonDatabaseProvider::class,
];
