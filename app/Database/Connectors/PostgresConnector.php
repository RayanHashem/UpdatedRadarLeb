<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

/**
 * PostgreSQL connector that adds connect_timeout to the DSN for slow or remote DBs (e.g. AWS RDS).
 */
class PostgresConnector extends BasePostgresConnector
{
    /**
     * Create a DSN string from a configuration, including connect_timeout when set.
     */
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $timeout = $config['connect_timeout'] ?? null;
        if ($timeout !== null && $timeout !== '') {
            $dsn .= ';connect_timeout=' . (int) $timeout;
        }

        return $dsn;
    }
}
