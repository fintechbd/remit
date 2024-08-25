<?php

use Fintech\Core\Facades\Core;
use Fintech\Remit\Http\Controllers\AssignVendorController;
use Fintech\Remit\Http\Controllers\BankTransferController;
use Fintech\Remit\Http\Controllers\CashPickupController;
use Fintech\Remit\Http\Controllers\VendorTestController;
use Fintech\Remit\Http\Controllers\WalletTransferController;
use Fintech\Remit\Http\Controllers\WalletVerificationController;
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
        ->middleware(config('fintech.auth.middleware'))->group(function () {
            if (Core::packageExists('Transaction')) {
                Route::prefix('assign-vendors')->name('assign-vendors.')
                    ->controller(AssignVendorController::class)
                    ->group(function () {
                        Route::get('available/{order_id}', 'available')->name('available');
                        Route::post('quote', 'quotation')->name('quotation');
                        Route::post('process', 'process')->name('process');
                        Route::get('status/{order_id}', 'tracker')->name('tracker');
                        Route::get('release/{order_id}', 'release')->name('release');
                        Route::post('cancel', 'cancel')->name('cancel');
                        Route::post('amendment', 'amendment')->name('amendment');
                        Route::get('overwrite/{order_id}', 'overwrite')->name('overwrite');
                    });
            }
            Route::apiResource('bank-transfers', BankTransferController::class)->except('update', 'destroy');
            Route::group(['prefix' => 'bank-transfers'], function () {
                Route::post('store-without-insufficient-balance', [BankTransferController::class, 'storeWithoutInsufficientBalance'])
                    ->name('store-without-insufficient-balance');
            });
            Route::apiResource('cash-pickups', CashPickupController::class)->except('update', 'destroy');
            Route::group(['prefix' => 'cash-pickups'], function () {
                Route::post('store-without-insufficient-balance', [CashPickupController::class, 'storeWithoutInsufficientBalance'])
                    ->name('store-without-insufficient-balance');
            });
            Route::apiResource('wallet-transfers', WalletTransferController::class)->except('update', 'destroy');
            Route::group(['prefix' => 'wallet-transfers'], function () {
                Route::post('store-without-insufficient-balance', [WalletTransferController::class, 'storeWithoutInsufficientBalance'])
                    ->name('store-without-insufficient-balance');
            });
            Route::post('wallet-verification', WalletVerificationController::class)->name('wallet-verification');
            Route::get('islami-bank-account-type-code', [VendorTestController::class, 'islamiBankAccountTypeCode'])->name('islami-bank-account-type-code');

            //DO NOT REMOVE THIS LINE//

            Route::prefix('charts')->name('charts.')->group(function () {
                Route::get('withdraw-partner-summary', \Fintech\Remit\Http\Controllers\Charts\WithdrawPartnerSummaryController::class)
                    ->name('withdraw-partner-summary');
            });
        });
}
