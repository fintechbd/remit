<?php

use Fintech\Remit\Http\Controllers\VendorTestController;
use Illuminate\Support\Facades\Route;

Route::get('islami-bank-fetch-balance', [VendorTestController::class, 'islamiBankFetchBalance'])
    ->name('islami-bank-fetch-balance');

Route::get('islami-bank-fetch-account-detail', [VendorTestController::class, 'islamiBankFetchAccountDetail'])
    ->name('islami-bank-fetch-account-detail');

Route::get('islami-bank-fetch-remittance-card', [VendorTestController::class, 'islamiBankFetchRemittanceCard'])
    ->name('islami-bank-fetch-remittance-card');

Route::get('islami-bank-fetch-mobile-banking-m-cash', [VendorTestController::class, 'islamiBankFetchMobileBankingMCash'])
    ->name('islami-bank-fetch-mobile-banking-m-cash');

Route::get('islami-bank-fetch-remittance-status', [VendorTestController::class, 'islamiBankFetchRemittanceStatus'])
    ->name('islami-bank-fetch-remittance-status');

Route::get('islami-bank-validate-beneficiary-wallet', [VendorTestController::class, 'islamiBankValidateBeneficiaryWallet'])
    ->name('islami-bank-fetch-validate-beneficiary-wallet');
