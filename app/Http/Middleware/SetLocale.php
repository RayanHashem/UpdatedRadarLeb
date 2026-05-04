<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the active locale on every web request.
 *
 * Order of precedence:
 *   1. Authenticated user's `locale` column (if it exists and is supported).
 *   2. Session value `locale` (set by LocaleController).
 *   3. config/app.php's `app.locale` default.
 *
 * Validates against `config/contact.php`'s `locales` allowlist so a malicious
 * cookie or session value can't trick the app into setting an unsupported
 * locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('contact.locales', ['en' => 'English']));
        $default   = config('app.locale', 'en');

        $locale = $default;

        // 2. session preference
        if ($request->hasSession() && in_array($request->session()->get('locale'), $supported, true)) {
            $locale = $request->session()->get('locale');
        }

        // 1. user preference (overrides session if logged-in user has set one)
        $user = $request->user();
        if ($user && isset($user->locale) && in_array($user->locale, $supported, true)) {
            $locale = $user->locale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
