<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "API" middleware group. Enjoy building your API!
|
*/
if (Config::get('fintech.remit.enabled')) {
    Route::prefix('remit')->name('remit.')
        ->middleware(config('fintech.auth.middleware'))
        ->group(function () {
            if (\Fintech\Core\Facades\Core::packageExists('Transaction')) {
                Route::get('assignable-vendors/{order_id}',
                    [\Fintech\Remit\Http\Controllers\AssignVendorController::class, 'available'])
                    ->name('assignable-vendors.available');
            }
            Route::apiResource('bank-transfers', \Fintech\Remit\Http\Controllers\BankTransferController::class)->except('update', 'destroy');
            Route::apiResource('cash-pickups', \Fintech\Remit\Http\Controllers\CashPickupController::class)->except('update', 'destroy');
            Route::apiResource('wallet-transfers', \Fintech\Remit\Http\Controllers\WalletTransferController::class)->except('update', 'destroy');

            Route::post('wallet-verification', \Fintech\Remit\Http\Controllers\WalletVerificationController::class)->name('wallet-verification');

            //DO NOT REMOVE THIS LINE//
        });
}
