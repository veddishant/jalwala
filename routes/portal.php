<?php

use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('portal/register/{tenant:slug}', [RegisterController::class, 'create'])
    ->middleware('guest')
    ->name('portal.register');
Route::post('portal/register/{tenant:slug}', [RegisterController::class, 'store'])
    ->middleware('guest')
    ->name('portal.register.store');

Route::middleware(['auth', 'verified', 'tenant', 'role:customer'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::inertia('/', 'portal/dashboard')->name('dashboard');
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    });
