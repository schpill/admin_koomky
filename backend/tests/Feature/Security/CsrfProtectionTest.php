<?php

use Illuminate\Routing\Route as RoutingRoute;

test('state-changing web routes keep csrf middleware enabled', function () {
    /** @var list<RoutingRoute> $postRoutes */
    $postRoutes = app('router')->getRoutes()->getRoutesByMethod()['POST'] ?? [];

    $webhookRoute = collect($postRoutes)
        ->first(fn (RoutingRoute $route): bool => $route->uri() === 'webhooks/email');

    expect($webhookRoute)->not->toBeNull();

    $middleware = collect($webhookRoute?->gatherMiddleware() ?? []);

    expect($middleware->contains('web'))->toBeTrue();
});

test('api post routes do not include csrf middleware', function () {
    /** @var list<RoutingRoute> $postRoutes */
    $postRoutes = app('router')->getRoutes()->getRoutesByMethod()['POST'] ?? [];

    $apiRoute = collect($postRoutes)
        ->first(fn (RoutingRoute $route): bool => $route->uri() === 'api/v1/auth/login');

    expect($apiRoute)->not->toBeNull();

    $middleware = collect($apiRoute?->gatherMiddleware() ?? []);

    expect($middleware->contains('web'))->toBeFalse();
});
