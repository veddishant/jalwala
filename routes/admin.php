<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerDepositController;
use App\Http\Controllers\Admin\CustomerWalletController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SubscriptionController;
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

            Route::get('customers/{managedCustomer}/wallet', [CustomerWalletController::class, 'show'])
                ->middleware('permission:wallet.view')
                ->name('customers.wallet');
            Route::post('customers/{managedCustomer}/wallet/top-up', [CustomerWalletController::class, 'topUp'])
                ->middleware('permission:wallet.top-up')
                ->name('customers.wallet.top-up');
            Route::post('customers/{managedCustomer}/wallet/adjust', [CustomerWalletController::class, 'adjust'])
                ->middleware('permission:wallet.adjust')
                ->name('customers.wallet.adjust');
            Route::patch('customers/{managedCustomer}/wallet/threshold', [CustomerWalletController::class, 'updateThreshold'])
                ->middleware('permission:wallet.adjust')
                ->name('customers.wallet.threshold');

            Route::get('customers/{managedCustomer}/deposits', [CustomerDepositController::class, 'show'])
                ->middleware('permission:deposits.view')
                ->name('customers.deposits');
            Route::post('customers/{managedCustomer}/deposits/collect', [CustomerDepositController::class, 'collect'])
                ->middleware('permission:deposits.collect')
                ->name('customers.deposits.collect');
            Route::post('customers/{managedCustomer}/deposits/refund', [CustomerDepositController::class, 'refund'])
                ->middleware('permission:deposits.refund')
                ->name('customers.deposits.refund');
            Route::post('customers/{managedCustomer}/deposits/adjust', [CustomerDepositController::class, 'adjust'])
                ->middleware('permission:deposits.adjust')
                ->name('customers.deposits.adjust');
        });

        Route::middleware('permission:products.view')->group(function (): void {
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/create', [ProductController::class, 'create'])
                ->middleware('permission:products.create')
                ->name('products.create');
            Route::post('products', [ProductController::class, 'store'])
                ->middleware('permission:products.create')
                ->name('products.store');
            Route::get('products/{managedProduct}/edit', [ProductController::class, 'edit'])
                ->middleware('permission:products.update')
                ->name('products.edit');
            Route::put('products/{managedProduct}', [ProductController::class, 'update'])
                ->middleware('permission:products.update')
                ->name('products.update');
            Route::patch('products/{managedProduct}/price', [ProductController::class, 'updatePrice'])
                ->middleware('permission:products.update')
                ->name('products.update-price');
            Route::post('products/{managedProduct}/deactivate', [ProductController::class, 'deactivate'])
                ->middleware('permission:products.deactivate')
                ->name('products.deactivate');
            Route::post('products/{managedProduct}/activate', [ProductController::class, 'activate'])
                ->middleware('permission:products.deactivate')
                ->name('products.activate');
        });

        Route::middleware('permission:orders.view')->group(function (): void {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/create', [OrderController::class, 'create'])
                ->middleware('permission:orders.create')
                ->name('orders.create');
            Route::post('orders', [OrderController::class, 'store'])
                ->middleware('permission:orders.create')
                ->name('orders.store');
            Route::get('orders/{managedOrder}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('orders/{managedOrder}/confirm', [OrderController::class, 'confirm'])
                ->middleware('permission:orders.confirm')
                ->name('orders.confirm');
            Route::post('orders/{managedOrder}/cancel', [OrderController::class, 'cancel'])
                ->middleware('permission:orders.cancel')
                ->name('orders.cancel');
            Route::post('orders/{managedOrder}/transition', [OrderController::class, 'transition'])
                ->middleware('permission:orders.update')
                ->name('orders.transition');
        });

        Route::middleware('permission:subscriptions.view')->group(function (): void {
            Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('subscriptions/create', [SubscriptionController::class, 'create'])
                ->middleware('permission:subscriptions.create')
                ->name('subscriptions.create');
            Route::post('subscriptions', [SubscriptionController::class, 'store'])
                ->middleware('permission:subscriptions.create')
                ->name('subscriptions.store');
            Route::get('subscriptions/{managedSubscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
            Route::get('subscriptions/{managedSubscription}/edit', [SubscriptionController::class, 'edit'])
                ->middleware('permission:subscriptions.update')
                ->name('subscriptions.edit');
            Route::put('subscriptions/{managedSubscription}', [SubscriptionController::class, 'update'])
                ->middleware('permission:subscriptions.update')
                ->name('subscriptions.update');
            Route::post('subscriptions/{managedSubscription}/pause', [SubscriptionController::class, 'pause'])
                ->middleware('permission:subscriptions.pause')
                ->name('subscriptions.pause');
            Route::post('subscriptions/{managedSubscription}/resume', [SubscriptionController::class, 'resume'])
                ->middleware('permission:subscriptions.resume')
                ->name('subscriptions.resume');
            Route::post('subscriptions/{managedSubscription}/cancel', [SubscriptionController::class, 'cancel'])
                ->middleware('permission:subscriptions.cancel')
                ->name('subscriptions.cancel');
        });
    });
