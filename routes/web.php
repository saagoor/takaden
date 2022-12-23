<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Takaden\Controllers\CheckoutController;
use Takaden\Controllers\CouponsController;

Route::prefix(config('takaden.checkout.route_prefix', 'takaden/checkout'))
    ->name('takaden.checkout.')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->group(function () {
        Route::post('initiate/{provider?}', [CheckoutController::class, 'initiate'])->name('initiate');
        Route::post('execute/{provider?}', [CheckoutController::class, 'execute'])->name('execute');
        Route::any('redirection/{provider?}', [CheckoutController::class, 'redirection'])->name('redirection');
        Route::any('success/{provider?}', [CheckoutController::class, 'success'])->name('success');
        Route::any('failure/{provider?}', [CheckoutController::class, 'failure'])->name('failure');
        Route::any('cancel/{provider?}', [CheckoutController::class, 'cancel'])->name('cancel');
        Route::post('webhook/{provider?}', [CheckoutController::class, 'webhook'])->name('webhook');
    });

Route::post('checkout/coupons/apply', [CouponsController::class, 'apply']);
