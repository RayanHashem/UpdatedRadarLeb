<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;
use Illuminate\Support\Facades\Log;

/**
 * PostgreSQL connector with connect_timeout in DSN and automatic retry
 * for slow/remote DBs (e.g. AWS RDS over the internet).
 */
class PostgresConnector extends BasePostgresConnector
{
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $timeout = $config['connect_timeout'] ?? null;
        if ($timeout !== null && $timeout !== '') {
            $dsn .= ';connect_timeout=' . (int) $timeout;
        }

        return $dsn;
    }

    /**
     * Retry the PDO connection up to 3 times with a short delay.
     * Remote RDS connections occasionally fail on the first attempt
     * due to DNS resolution, TCP handshake, or TLS negotiation delays.
     */
    public function connect(array $config): \PDO
    {
        $maxAttempts = (int) ($config['connect_retries'] ?? 3);
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return parent::connect($config);
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    Log::warning("DB connect attempt {$attempt}/{$maxAttempts} failed, retrying…", [
                        'error' => $e->getMessage(),
                    ]);
                    usleep(500_000 * $attempt);
                }
            }
        }

        throw $lastException;
    }
}
