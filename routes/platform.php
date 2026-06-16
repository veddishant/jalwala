<?php

use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'role:super-admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:platform.tenants.create')->group(function (): void {
            Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
            Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        });

        Route::middleware('permission:platform.tenants.view')->group(function (): void {
            Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');

            Route::post('impersonate/{tenant}', [ImpersonationController::class, 'store'])
                ->name('impersonate.store');
            Route::delete('impersonate', [ImpersonationController::class, 'destroy'])
                ->name('impersonate.destroy');
        });

        Route::middleware('permission:platform.tenants.update')->group(function (): void {
            Route::get('tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
            Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
            Route::get('tenants/{tenant}/settings', [TenantController::class, 'editSettings'])->name('tenants.settings.edit');
            Route::put('tenants/{tenant}/settings', [TenantController::class, 'updateSettings'])->name('tenants.settings.update');
        });

        Route::middleware('permission:platform.tenants.suspend')->group(function (): void {
            Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
            Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        });
    });
