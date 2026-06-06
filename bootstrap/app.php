<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind nginx/a load balancer terminating TLS, trust the proxy's X-Forwarded-*
        // headers so Laravel sees the real client IP + https scheme. Without this, secure
        // cookies and route()/url() https generation break. `at: '*'` is appropriate when
        // the only ingress is our own reverse proxy (the container isn't directly exposed).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // When an unauthenticated user visits /portal, redirect to portal login.
        // Handles the case where Filament/policy throws 403 mid-request after the
        // user is already on a portal page (e.g. session expiry).
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 403 && str_starts_with($request->path(), 'portal') && ! $request->user()) {
                return redirect('/portal/login');
            }
        });

        // Handles the bare unauthenticated case — typing /portal directly while
        // logged out throws AuthenticationException (not HttpException), so the
        // 403 handler above never sees it and Laravel's default just returns a
        // framework 403 page. Send them to the portal login form instead.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if (str_starts_with($request->path(), 'portal') && ! $request->expectsJson()) {
                return redirect()->guest('/portal/login');
            }
        });
    })->create();
