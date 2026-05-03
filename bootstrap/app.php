<?php

require_once __DIR__ . '/../app/Helpers/functions.php';

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
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
        $proxies = env('APP_TRUSTED_PROXIES');
        if ($proxies !== null && $proxies !== '') {
            $at = $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies));
            $middleware->trustProxies(at: $at);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * CSRF 419: Inertia would otherwise show the HTML error in a modal. Force a
         * client-side full location visit with a fresh session + XSRF-TOKEN cookie
         * (see https://inertiajs.com/csrf-protection).
         */
        $exceptions->renderable(function (TokenMismatchException $e, Request $request) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            return null;
        });

        $exceptions->respond(function (mixed $response, \Throwable $e, Request $request) {
            if ($e instanceof HttpException && $e->getStatusCode() === 403 && str_starts_with($request->path(), 'admin')) {
                auth('admin')->logout();
                return redirect()->route('filament.admin.auth.login');
            }

            $isAdminRoute = str_starts_with($request->path(), 'admin')
                || str_starts_with($request->path(), 'livewire');

            if ($isAdminRoute && $e instanceof QueryException && \App\Helpers\isDbConnectionError($e)) {
                $loginUrl = '/admin/login';

                if ($request->expectsJson() || $request->header('X-Livewire')) {
                    return response()->json([
                        'message' => 'Database temporarily unavailable. Please refresh the page.',
                    ], 503);
                }

                return redirect($loginUrl)
                    ->with('notification', [
                        'title' => 'Database temporarily unavailable',
                        'body' => 'Could not reach the database. Please wait a moment and try again.',
                        'status' => 'danger',
                    ]);
            }

            return $response;
        });
    })->create();
