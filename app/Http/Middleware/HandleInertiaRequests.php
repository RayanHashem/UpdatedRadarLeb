<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin', 'admin/*', 'livewire/*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // i18n payload — see resources/js/composables/useTranslate.ts.
            'locale' => app()->getLocale(),
            'locales' => config('contact.locales'),
            'translations' => $this->loadTranslations(app()->getLocale()),

            // Public values we used to hardcode in Vue components.
            'contact' => [
                'phone' => config('contact.support_phone'),
            ],
        ];
    }

    /**
     * Read the translation map for $locale from lang/<locale>.json.
     *
     * We deliberately ship the full map per request so Vue's useTranslate()
     * stays synchronous. The file is small (a few KB) and the JSON parses
     * are cheap; we cache the decoded array statically to avoid re-reading
     * on the same request lifecycle.
     */
    protected function loadTranslations(string $locale): array
    {
        static $cache = [];

        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $path = base_path("lang/{$locale}.json");

        if (! is_file($path)) {
            return $cache[$locale] = [];
        }

        return $cache[$locale] = json_decode(file_get_contents($path), true) ?? [];
    }
}
