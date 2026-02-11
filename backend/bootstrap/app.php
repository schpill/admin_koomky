<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\DisplayExceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\Google2FAServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            return Application::configure(basePath: dirname(__DIR__))
                ->withExceptions(function (Exceptions $exceptions) {
                    $exceptions->stopIgnoring(
                        E_USER_DEPRECATED,
                    );
                })
                ->withDisplayExceptions(function (DisplayExceptions $displayExceptions) {
                    $displayExceptions
                        ->debug(config('app.debug'))
                        ->withLogger();
                });
        },
    );
