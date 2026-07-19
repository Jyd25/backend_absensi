<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Connectors\NeonPgSqlConnector;

class NeonDatabaseProvider extends ServiceProvider
{
    public function register(): void
    {
        if (env('DB_NEON_ENDPOINT')) {
            $this->app->bind('db.connector.pgsql', function () {
                return new NeonPgSqlConnector;
            });
        }
    }
}
