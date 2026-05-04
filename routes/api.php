<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Application JSON endpoints currently live in routes/auth.php (because they
| share the auth middleware group). This file holds only operational
| endpoints like the /db-check health probe.
|
*/

/*
 * /db-check — operational health probe.
 *
 * Allowed only when:
 *   • APP_ENV=local (developer convenience), OR
 *   • a valid ?secret=… matches HEALTH_CHECK_SECRET in .env
 *
 * The previous version had a broken OR clause that allowed access from
 * staging/non-prod without any secret. The new logic is two strict
 * conditions joined by OR — easy to read, easy to test, and impossible to
 * flip on by accident.
 *
 * The body intentionally does NOT echo the database driver, version, or
 * connection error message — those leak operational metadata to anyone
 * who triggers a 500.
 */
Route::middleware('throttle:30,1')->get('/db-check', function () {
    // Both the env tag and the secret are read via config() instead of env()
    // for two reasons: (a) `php artisan config:cache` strands env() reads in
    // production, and (b) PHPUnit can override config() at runtime but cannot
    // reliably override env() once Laravel's Env repository has been built.
    $isLocal      = config('app.env') === 'local';
    $secret       = config('app.health_check_secret');
    $hasGoodToken = is_string($secret)
        && $secret !== ''
        && hash_equals($secret, (string) request()->query('secret', ''));

    if (! ($isLocal || $hasGoodToken)) {
        abort(404);
    }

    try {
        DB::connection()->getPdo();
        DB::select('SELECT 1 as ok');

        return response()->json(['status' => 'ok']);
    } catch (\Throwable $e) {
        report($e);

        return response()->json(['status' => 'fail'], 503);
    }
});
