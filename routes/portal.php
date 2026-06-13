<?php

use App\Http\Controllers\Portal\DepositController;
use App\Http\Controllers\Portal\OrderController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\RegisterController;
use App\Http\Controllers\Portal\SubscriptionController;
use App\Http\Controllers\Portal\WalletController;
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
        Route::get('wallet', [WalletController::class, 'index'])
            ->middleware('permission:wallet.view')
            ->name('wallet.index');
        Route::get('deposits', [DepositController::class, 'index'])
            ->middleware('permission:deposits.view')
            ->name('deposits.index');

        Route::middleware('permission:orders.view')->group(function (): void {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/create', [OrderController::class, 'create'])
                ->middleware('permission:orders.create')
                ->name('orders.create');
            Route::post('orders', [OrderController::class, 'store'])
                ->middleware('permission:orders.create')
                ->name('orders.store');
            Route::get('orders/{managedOrder}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('orders/{managedOrder}/cancel', [OrderController::class, 'cancel'])
                ->middleware('permission:orders.cancel')
                ->name('orders.cancel');
        });

        Route::middleware('permission:subscriptions.view')->group(function (): void {
            Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription.show');
            Route::post('subscription/pause', [SubscriptionController::class, 'pause'])
                ->middleware('permission:subscriptions.pause')
                ->name('subscription.pause');
            Route::post('subscription/resume', [SubscriptionController::class, 'resume'])
                ->middleware('permission:subscriptions.resume')
                ->name('subscription.resume');
        });
    });
