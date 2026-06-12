<?php

use Illuminate\Support\Facades\Route;

test('admin routes use web middleware for session support', function () {
    $route = collect(Route::getRoutes()->getRoutesByName())
        ->get('admin.users.index');

    expect($route)->not->toBeNull()
        ->and($route->middleware())->toContain('web');
});

test('agent and portal routes use web middleware for session support', function () {
    $agentRoute = collect(Route::getRoutes()->getRoutesByName())
        ->get('agent.dashboard');
    $portalRoute = collect(Route::getRoutes()->getRoutesByName())
        ->get('portal.dashboard');

    expect($agentRoute)->not->toBeNull()
        ->and($agentRoute->middleware())->toContain('web')
        ->and($portalRoute)->not->toBeNull()
        ->and($portalRoute->middleware())->toContain('web');
});
