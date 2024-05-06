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

Route::get('islami-bank-fetch-validate-beneficiary-wallet', [VendorTestController::class, 'islamiBankValidateBeneficiaryWallet'])
    ->name('islami-bank-fetch-validate-beneficiary-wallet');

Route::get('islami-bank-spot-cash', [VendorTestController::class, 'islamiBankSpotCash'])
    ->name('islami-bank-spot-cash');

Route::get('islami-bank-account-payee', [VendorTestController::class, 'islamiBankAccountPayee'])
    ->name('islami-bank-account-payee');

Route::get('islami-bank-third-bank', [VendorTestController::class, 'islamiBankThirdBank'])
    ->name('islami-bank-third-bank');

Route::get('islami-bank-mobile-banking-m-cash', [VendorTestController::class, 'islamiBankMobileBankingMCash'])
    ->name('islami-bank-mobile-banking-m-cash');

Route::get('islami-bank-remittance-card', [VendorTestController::class, 'islamiBankRemittanceCard'])
    ->name('islami-bank-remittance-card');
