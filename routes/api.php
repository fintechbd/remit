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

            Route::apiResource('bank-transfers', \Fintech\Remit\Http\Controllers\BankTransferController::class)->except('update', 'destroy');
            //Route::post('bank-transfers/{bank_transfer}/restore', [\Fintech\Remit\Http\Controllers\BankTransferController::class, 'restore'])->name('bank-transfers.restore');
            Route::post('bank-transfers/{bank_transfer}/assign-vendor', [\Fintech\Remit\Http\Controllers\BankTransferController::class, 'assignVendor'])->name('bank-transfers.assign-vendor');

            Route::apiResource('cash-pickups', \Fintech\Remit\Http\Controllers\CashPickupController::class)->except('update', 'destroy');
            //Route::post('cash-pickups/{cash_pickup}/restore', [\Fintech\Remit\Http\Controllers\CashPickupController::class, 'restore'])->name('cash-pickups.restore');
            Route::post('cash-pickups/{cash_pickup}/assign-vendor', [\Fintech\Remit\Http\Controllers\CashPickupController::class, 'assignVendor'])->name('cash-pickups.assign-vendor');

            Route::apiResource('wallet-transfers', \Fintech\Remit\Http\Controllers\WalletTransferController::class)->except('update', 'destroy');
            //Route::post('wallet-transfers/{wallet_transfer}/restore', [\Fintech\Remit\Http\Controllers\WalletTransferController::class, 'restore'])->name('wallet-transfers.restore');
            Route::post('wallet-transfers/{wallet_transfer}/assign-vendor', [\Fintech\Remit\Http\Controllers\WalletTransferController::class, 'assignVendor'])->name('wallet-transfers.assign-vendor');

            Route::post('wallet-verification', \Fintech\Remit\Http\Controllers\WalletVerificationController::class)->name('wallet-verification');

            //DO NOT REMOVE THIS LINE//
        });
}
