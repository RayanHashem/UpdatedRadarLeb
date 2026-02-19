<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// API routes can be added here if needed
// Currently, API endpoints are in routes/auth.php

// DB health check: only when APP_ENV is local or when ?secret=HEALTH_CHECK_SECRET is provided (production)
Route::get('/db-check', function () {
    $allow = app()->environment('local') || (
        config('app.env') !== 'production' ||
        (request()->filled('secret') && request()->query('secret') === env('HEALTH_CHECK_SECRET'))
    );
    if (! $allow) {
        abort(404);
    }
    try {
        $pdo = DB::connection()->getPdo();
        $result = DB::select('SELECT current_database() as db, version() as version');
        return response()->json([
            'status' => 'SUCCESS',
            'database' => $result[0]->db,
            'driver' => config('database.default'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'driver' => config('database.default'),
        ], 500);
    }
});


