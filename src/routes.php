<?php

use Takaden\Controllers\CouponsController;
use App\Http\Middleware\VerifyCsrfToken;
use Takaden\Controllers\PaymentController;
use Takaden\Controllers\PaymentHistoryController;
use Takaden\Controllers\PurchaseHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment')
    ->name('payment.')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->group(function () {
        Route::post('validate', [PaymentController::class, 'validatePurchase'])->name('validate');
        Route::any('create', [PaymentController::class, 'create'])->name('create');
        Route::any('success', [PaymentController::class, 'success'])->name('success');
        Route::any('failure', [PaymentController::class, 'failure'])->name('failure');
        Route::any('cancel', [PaymentController::class, 'cancel'])->name('cancel');
        Route::any('webhook', [PaymentController::class, 'webhook'])->name('webhook');
    });

Route::post('payment/coupons/apply', [CouponsController::class, 'apply']);

Route::middleware('auth:api')->group(function () {
    Route::get('user/purchases', PurchaseHistoryController::class);
    Route::get('user/payments', PaymentHistoryController::class);
});
