<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RadarController extends Controller
{
    /*
     * Dashboard.vue polls /radar/status on an interval. On `php artisan serve`
     * (single-threaded on Windows) every request serialises on the user's
     * session file lock, so when a poll is slow the next poll is queued on top
     * of it and the queue explodes, starving all other asset requests (icons,
     * logo, game-bg). Two mitigations applied here:
     *
     *  1. save() the session immediately so the lock is released for the
     *     other queued requests (icons/audio/videos).
     *  2. cache the scans_enabled flag for 15 s. The admin can still flip it
     *     and users will pick it up within that window.
     */
    public function status(Request $request)
    {
        $online = (bool) Cache::remember('radar_status_online', 15, function () {
            return SystemSetting::get('scans_enabled', true);
        });

        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->json([
            'online' => $online,
        ])->header('Cache-Control', 'private, max-age=10');
    }
}
