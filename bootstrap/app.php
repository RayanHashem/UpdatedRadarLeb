<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\PortBasedSessionIsolation::class);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        // Trust ALB/CloudFront proxy headers (set APP_TRUSTED_PROXIES=* or comma-separated IPs in production)
        $proxies = env('APP_TRUSTED_PROXIES');
        if ($proxies !== null && $proxies !== '') {
            $at = $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
            $middleware->trustProxies(at: $at);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (TokenMismatchException $e) {
            $request = request();
            Log::channel('single')->warning('419 TokenMismatchException – diagnostic', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'cookie_header' => $request->header('Cookie'),
                'x_xsrf_token' => $request->header('X-XSRF-TOKEN'),
                'x_csrf_token' => $request->header('X-CSRF-TOKEN'),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'session_keys' => $request->hasSession() ? array_keys($request->session()->all()) : [],
                'config_app_url' => config('app.url'),
                'config_session_domain' => config('session.domain'),
                'config_session_cookie' => config('session.cookie'),
                'config_session_secure' => config('session.secure'),
                'config_session_same_site' => config('session.same_site'),
                'config_session_path' => config('session.path'),
            ]);
        });

        $exceptions->respond(function (mixed $response, \Throwable $e, Request $request) {
            if ($e instanceof HttpException && $e->getStatusCode() === 403 && str_starts_with($request->path(), 'admin')) {
                auth()->logout();

                return redirect()->route('filament.admin.auth.login');
            }

            return $response;
        });
    })->create();

