<?php

namespace App\Http\Responses\Filament;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class AdminLogoutResponse implements LogoutResponseContract
{
    /**
     * Redirect to admin login page after logout.
     * Ensures redirect stays on the same port/host (e.g., localhost:8001/admin/login).
     */
    public function toResponse($request): RedirectResponse
    {
        // Ensure URL is forced to current request's origin (preserves port)
        URL::forceRootUrl($request->getSchemeAndHttpHost());
        
        $panel = Filament::getPanel('admin');
        
        // Build login URL explicitly to ensure it uses current port/host
        $schemeAndHost = $request->getSchemeAndHttpHost();
        $adminPath = $panel->getPath(); // 'admin'
        $loginUrl = $schemeAndHost . '/' . $adminPath . '/login';
        
        return redirect()->to($loginUrl);
    }
}
