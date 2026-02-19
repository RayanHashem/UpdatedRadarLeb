<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPortIsolation
{
    /**
     * Force app URL to the request origin for admin so redirects and Livewire stay on the same port.
     * Session cookie/path are set in AppServiceProvider::boot() so they're in place before session is built.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! str_starts_with($request->path(), 'admin')) {
            return $next($request);
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
