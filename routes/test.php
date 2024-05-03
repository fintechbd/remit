<?php

use Fintech\Remit\Http\Controllers\VendorTestController;
use Illuminate\Support\Facades\Route;

Route::get('islami-bank-fetch-balance', [VendorTestController::class, 'islamiBankFetchBalance'])
    ->name('islami-bank-fetch-balance');
