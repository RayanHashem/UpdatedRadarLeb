<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Session / cookie isolation between the admin panel and the main app.
 *
 * The class name still says "PortBased…" for backward compat, but the actual
 * isolation is now URL-PATH based:
 *
 *   /admin/*     → radarleb_admin_session
 *   /livewire/* → radarleb_admin_session  (Filament uses Livewire)
 *   everything else → radarleb_main_session
 *
 * Why the change (root cause of the 419 "Page Expired" on /admin/login):
 * -----------------------------------------------------------------------
 * The old logic picked the cookie by listening port (8000 vs 8001). In
 * practice the main Vue/Inertia app AND the Filament admin panel are both
 * served by the same `php artisan serve --port=8001` process, so both routes
 * ended up with the same cookie name. Every `Auth::login()` call from the
 * main app regenerates the session to prevent fixation, which silently
 * invalidates the CSRF token that was embedded into the /admin/login form
 * a few minutes earlier, producing a 419 on the very next admin login POST.
 * A second attempt then works because the reloaded login page writes a fresh
 * token into the still-live main-app session — but one wrong move on the
 * main app and it breaks again.
 *
 * Splitting by path means the two "apps" each own a completely separate
 * session file, independent CSRF token, and independent auth state — no
 * more spurious 419s even when the operator flips between the two tabs.
 *
 * Runs as a global prepend (see bootstrap/app.php) so the session cookie
 * name is decided before Laravel's StartSession middleware reads it.
 */
class PortBasedSessionIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        /*
         * Root cause of the "Maximum execution time of 30 seconds exceeded"
         * that hit main-app /login POSTs:
         *
         * The database lives on AWS RDS (us-east-1), so every PDO connect
         * does TCP + TLS across the public internet. Under normal conditions
         * it's ~200 ms, but transient DNS / TCP resets push that to multiple
         * seconds, and our custom PostgresConnector retries up to 3 times
         * with growing back-off. PHP's default `max_execution_time` is 30 s,
         * so a single bad connect window is enough to wedge the login
         * request past that limit — PHP then aborts with a fatal before the
         * query even returns, and the user sees a 500 page.
         *
         * The admin panel already bumped to 120 s here to survive the same
         * hiccup; the main app got nothing. Applying the bump universally
         * (admin AND main) aligns both surfaces: neither fatals on a slow
         * RDS connect, and both recover as soon as the network clears.
         * We still prefer to fail fast at the connector layer (see
         * DB_CONNECT_TIMEOUT and the retry budget in PostgresConnector),
         * but this ceiling is the safety net that turns a 500 into a
         * ~2-second successful login.
         */
        @set_time_limit(120);

        if ($this->isAdminPath($path)) {
            config(['session.cookie' => 'radarleb_admin_session']);
        } else {
            config(['session.cookie' => 'radarleb_main_session']);
        }

        $root = $request->getSchemeAndHttpHost();
        config(['app.url' => $root]);
        URL::forceRootUrl($root);

        return $next($request);
    }

    /**
     * An "admin" request is anything the Filament panel or its Livewire
     * XHRs reach for. We intentionally include `livewire/*` because that's
     * how Filament posts form submissions (including the login form).
     */
    private function isAdminPath(string $path): bool
    {
        return $path === 'admin'
            || str_starts_with($path, 'admin/')
            || $path === 'livewire'
            || str_starts_with($path, 'livewire/');
    }
}
