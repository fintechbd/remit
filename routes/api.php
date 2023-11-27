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

            //DO NOT REMOVE THIS LINE//
        });
}
