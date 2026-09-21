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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Both deploy targets put this app behind a TLS-terminating proxy
        // (Nginx in front of PHP-FPM for docker-compose.prod.yml, Vercel's
        // edge in front of the FrankenPHP container for Dockerfile.vercel) -
        // the app itself only ever sees plain HTTP from that proxy. Without
        // trusting the proxy's forwarded headers, Laravel can't tell the
        // original request was HTTPS and generates http:// URLs (broken/
        // mixed-content asset and form URLs on an https:// page). '*' is
        // safe here because nothing but that one trusted proxy can reach
        // the app directly in either deploy's network setup.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
