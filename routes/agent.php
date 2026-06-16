<?php

use App\Http\Controllers\Agent\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'role:delivery-agent'])
    ->prefix('agent')
    ->name('agent.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
