<?php

use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupplierRegisterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('contact', [ContactInquiryController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::middleware('guest')->group(function (): void {
    Route::get('register/supplier', [SupplierRegisterController::class, 'create'])
        ->name('supplier.register');
    Route::post('register/supplier', [SupplierRegisterController::class, 'store'])
        ->name('supplier.register.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
