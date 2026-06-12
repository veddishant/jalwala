<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'role:customer'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::inertia('/', 'portal/dashboard')->name('dashboard');
    });
