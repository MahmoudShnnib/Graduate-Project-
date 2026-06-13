<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // Start a fresh audit batch per request (see AuditService::resetBatch).
        $middleware->append(\App\Http\Middleware\ResetAuditBatch::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Authenticated user on the wrong Filament panel (e.g. student hitting /admin/*).
        // Log out and send them to the panel they belong on — breaks redirect loops.
        $exceptions->render(function (HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            $user = $request->user();

            if ($user instanceof User) {
                $panelId = match (true) {
                    str_starts_with($request->path(), 'portal') => 'student',
                    str_starts_with($request->path(), 'admin') => 'admin',
                    default => null,
                };

                if ($panelId !== null && ! $user->canAccessPanel(Filament::getPanel($panelId))) {
                    $preferredPanelId = $user->preferredPanelId();

                    if ($preferredPanelId !== null && $preferredPanelId !== $panelId) {
                        auth()->logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return redirect(Filament::getPanel($preferredPanelId)->getLoginUrl());
                    }
                }
            }

            // When an unauthenticated user visits /portal, redirect to portal login.
            if (str_starts_with($request->path(), 'portal') && ! $request->user()) {
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
