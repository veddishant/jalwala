<?php

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
    });
