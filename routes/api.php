<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// API routes can be added here if needed
// Currently, API endpoints are in routes/auth.php

// Temporary DB health check - REMOVE AFTER TESTING
Route::get('/db-check', function () {
    try {
        $pdo = DB::connection()->getPdo();
        $result = DB::select('SELECT current_database() as db, version() as version');
        return response()->json([
            'status' => 'SUCCESS',
            'database' => $result[0]->db,
            'driver' => config('database.default'),
            'host' => config('database.connections.pgsql.host'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'driver' => config('database.default'),
            'host' => config('database.connections.pgsql.host'),
        ], 500);
    }
});


