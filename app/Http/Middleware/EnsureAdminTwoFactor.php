<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.admin_two_factor.enabled')) {
            return $next($request);
        }

        if ($request->is('admin/two-factor') || $request->is('admin/logout')) {
            return $next($request);
        }

        $verifiedAt = (int) $request->session()->get('admin_2fa_passed_at', 0);
        $expiresAt = $verifiedAt + ((int) config('security.admin_two_factor.remember_minutes', 480) * 60);

        if ($verifiedAt > 0 && $expiresAt > now()->timestamp) {
            return $next($request);
        }

        $request->session()->forget('admin_2fa_passed_at');

        return redirect()->guest(route('admin.two-factor.create'));
    }
}
