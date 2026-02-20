<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->alias([
            'two-factor' => \App\Http\Middleware\RequireTwoFactorAuthentication::class,
            'portal-auth' => \App\Http\Middleware\PortalAuthMiddleware::class,
            'abilities' => \App\Http\Middleware\CheckAbilities::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\PrometheusMiddleware::class,
            \App\Http\Middleware\RequestTelemetryMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
