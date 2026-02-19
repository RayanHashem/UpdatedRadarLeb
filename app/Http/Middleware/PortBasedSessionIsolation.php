<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Run first (global prepend) so session cookie name and app URL are set BEFORE
 * StartSession runs. This prevents main app and admin from sharing sessions:
 * - Dev: port 8001 = admin, 8000 = main.
 * - Production (single host): path /admin = admin cookie, else main cookie.
 */
class PortBasedSessionIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $port = (int) $request->getPort();
        $path = $request->path();
        $root = $request->getSchemeAndHttpHost();

        if ($port === 8001 || str_starts_with($path, 'admin')) {
            config(['session.cookie' => 'radarleb_admin_session']);
        } elseif ($port === 8000 || ! str_starts_with($path, 'admin')) {
            config(['session.cookie' => 'radarleb_main_session']);
        }

        config(['app.url' => $root]);
        URL::forceRootUrl($root);

        return $next($request);
    }
}
