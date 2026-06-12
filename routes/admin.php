<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'role:supplier-admin|super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])
                ->middleware('permission:users.create')
                ->name('users.create');
            Route::post('users', [UserController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');
            Route::get('users/{managedUser}/edit', [UserController::class, 'edit'])
                ->middleware('permission:users.update')
                ->name('users.edit');
            Route::put('users/{managedUser}', [UserController::class, 'update'])
                ->middleware('permission:users.update')
                ->name('users.update');
        });

        Route::middleware('permission:customers.view')->group(function (): void {
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/create', [CustomerController::class, 'create'])
                ->middleware('permission:customers.create')
                ->name('customers.create');
            Route::post('customers', [CustomerController::class, 'store'])
                ->middleware('permission:customers.create')
                ->name('customers.store');
            Route::get('customers/{managedCustomer}/edit', [CustomerController::class, 'edit'])
                ->middleware('permission:customers.update')
                ->name('customers.edit');
            Route::put('customers/{managedCustomer}', [CustomerController::class, 'update'])
                ->middleware('permission:customers.update')
                ->name('customers.update');
            Route::post('customers/{managedCustomer}/pause', [CustomerController::class, 'pause'])
                ->middleware('permission:customers.pause')
                ->name('customers.pause');
            Route::post('customers/{managedCustomer}/resume', [CustomerController::class, 'resume'])
                ->middleware('permission:customers.pause')
                ->name('customers.resume');
            Route::post('customers/{managedCustomer}/close', [CustomerController::class, 'close'])
                ->middleware('permission:customers.close')
                ->name('customers.close');
        });
    });
