<?php

namespace App\Http\Responses\Filament;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class AdminLoginResponse implements LoginResponseContract
{
    /**
     * Redirect admin users to the admin dashboard after login.
     * Ensures redirect stays on the same port/host (e.g., localhost:8001/admin).
     */
    public function toResponse($request): RedirectResponse
    {
        // Ensure URL is forced to current request's origin (preserves port)
        URL::forceRootUrl($request->getSchemeAndHttpHost());
        
        $panel = Filament::getPanel('admin');
        
        // Check if there's an intended URL that's within the admin panel
        $intended = $request->session()->pull('url.intended');
        if ($intended && str_contains($intended, '/admin')) {
            // Use intended URL if it's an admin route
            return redirect()->to($intended);
        }
        
        // Otherwise redirect to admin dashboard
        // Build URL explicitly to ensure it uses current port/host
        $schemeAndHost = $request->getSchemeAndHttpHost();
        $adminPath = $panel->getPath(); // 'admin'
        $dashboardUrl = $schemeAndHost . '/' . $adminPath;
        
        return redirect()->to($dashboardUrl);
    }
}
