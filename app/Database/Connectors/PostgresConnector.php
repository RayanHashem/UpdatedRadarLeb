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
     * Retry the PDO connection a small number of times with a short delay.
     *
     * Remote AWS RDS connections occasionally fail on the first attempt
     * due to DNS resolution, TCP handshake, or TLS negotiation delays, so
     * a single retry is useful. However the previous budget (3 attempts
     * with 500ms × attempt back-off and DB_CONNECT_TIMEOUT=15) could
     * consume up to ~46 seconds on a bad network before surfacing the
     * error — that's enough to breach PHP's 30s max_execution_time on the
     * main-app login POST and produce a fatal 500 ("Maximum execution
     * time of 30 seconds exceeded"). Dropping to 2 attempts with a flat
     * 200 ms pause, combined with DB_CONNECT_TIMEOUT=3, caps the worst
     * case at roughly 3 + 0.2 + 3 ≈ 6.4 seconds. That's well under the
     * 120-second ceiling set by PortBasedSessionIsolation and leaves
     * plenty of headroom for the rest of the login pipeline (session
     * regen, Inertia render, etc.).
     */
    public function connect(array $config): \PDO
    {
        $maxAttempts = (int) ($config['connect_retries'] ?? 2);
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
                    usleep(200_000);
                }
            }
        }

        throw $lastException;
    }
}
