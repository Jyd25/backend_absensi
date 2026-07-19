<?php

namespace App\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPgSqlConnector extends PostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $endpoint = $config['neon_endpoint'] ?? env('DB_NEON_ENDPOINT');
        if ($endpoint && !str_contains($dsn, 'options=')) {
            $dsn .= ";options='endpoint=$endpoint'";
        }

        return $dsn;
    }
}
