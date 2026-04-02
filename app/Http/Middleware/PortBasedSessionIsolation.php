<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set session cookie name by port so admin (8001) and main app (8000) don't share sessions.
 * Runs before everything else (global prepend). Pure port check — no path/referer/redirect logic.
 */
class PortBasedSessionIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $port = (int) $request->getPort();

        if ($port === 8001) {
            set_time_limit(120);
            config(['session.cookie' => 'radarleb_admin_session']);
        } else {
            config(['session.cookie' => 'radarleb_main_session']);
        }

        $root = $request->getSchemeAndHttpHost();
        config(['app.url' => $root]);
        URL::forceRootUrl($root);

        return $next($request);
    }
}
