<?php

use App\Http\Middleware\SessionExpiryHandler;
use App\Http\Middleware\SessionExtensionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Trust the reverse proxy (nginx) so request()->ip() resolves the real
        // client address from X-Forwarded-For instead of storing the proxy's
        // loopback (::1 / 127.0.0.1) in activity logs.
        $middleware->trustProxies(at: '*');

        // Add session management middleware to web middleware group.
        // AuthenticateSession enforces ENABLE_MULTI_SESSION_LOGOUT: it stores the
        // user's password hash per session and logs out any session whose hash no
        // longer matches after Auth::logoutOtherDevices() rehashes the password.
        $middleware->web(append: [
            SessionExtensionMiddleware::class,
            SessionExpiryHandler::class,
            AuthenticateSession::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'session.extend' => SessionExtensionMiddleware::class,
            'auth.session' => AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // No legacy URL 301 redirect needed — HasPublicId::resolveRouteBinding()
        // accepts BOTH numeric ids and ULIDs as URL parameters, so /cities/123
        // and /cities/01HXYZ... both resolve to the same model directly.
    })->create();
