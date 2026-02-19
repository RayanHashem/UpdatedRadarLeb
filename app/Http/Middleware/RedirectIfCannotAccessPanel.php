<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfCannotAccessPanel
{
    /**
     * If the user is logged in but cannot access the admin panel (e.g. main-app user),
     * log them out and redirect to the panel login so they can sign in with an admin account.
     * We resolve the panel by path because getCurrentPanel() is not set yet when this runs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! str_starts_with($request->path(), 'admin')) {
            return $next($request);
        }

        // Avoid timeout when DB (e.g. RDS) is slow; admin dashboard/widgets can be heavy
        set_time_limit(120);

        $panel = Filament::getPanel('admin');
        $user = $request->user();

        if ($user && method_exists($user, 'canAccessPanel') && ! $user->canAccessPanel($panel)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to($panel->route('auth.login'));
        }

        return $next($request);
    }
}
