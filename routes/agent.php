<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'role:delivery-agent'])
    ->prefix('agent')
    ->name('agent.')
    ->group(function (): void {
        Route::inertia('/', 'agent/dashboard')->name('dashboard');
    });
