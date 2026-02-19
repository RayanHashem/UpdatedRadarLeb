<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    /**
     * Ensure default database connection works (pgsql or sqlite).
     * Skips when DB is in-memory or when pgsql is set to :memory: (phpunit env).
     */
    public function test_database_connection_works(): void
    {
        $driver = config('database.default');
        $database = config("database.connections.{$driver}.database");

        if ($driver === 'sqlite' && ($database === ':memory:' || $database === '')) {
            $this->markTestSkipped('Using in-memory sqlite; real connection not tested.');
        }
        if ($driver === 'pgsql' && $database === ':memory:') {
            $this->markTestSkipped('Test env has pgsql with :memory:; use DB_CONNECTION=sqlite for unit tests.');
        }

        $this->assertNotNull(DB::connection()->getPdo());
        $this->assertTrue(DB::connection()->getDatabaseName() !== null || $driver === 'sqlite');
    }
}
