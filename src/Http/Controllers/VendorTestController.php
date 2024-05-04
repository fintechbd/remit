<?php

namespace Fintech\Remit\Http\Controllers;

use Illuminate\Routing\Controller;

class VendorTestController extends Controller
{
    public function islamiBankFetchBalance(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        dump($vendor->fetchBalance('BDT'));
    }

    public function islamiBankFetchAccountDetail(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['account_number'] = '11052';
        $data['account_type'] = '10';
        $data['branch_code'] = '213';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankFetchRemittanceStatus(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['transaction_reference_number'] = 'GIT4296253';
        $data['secret_key'] = '';
        dump($vendor->fetchRemittanceStatus($data));
    }

    public function islamiBankValidateBeneficiaryWallet(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['account_number'] = '01614747054';
        $data['paymentType'] = '7';
        dump($vendor->validateBeneficiaryWallet($data));
    }

    public function islamiBankFetchRemittanceCard(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['account_number'] = '34';
        $data['account_type'] = '71';
        $data['branch_code'] = '123';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankFetchMobileBankingMCash(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['account_number'] = '01670697200';
        $data['account_type'] = '';
        $data['branch_code'] = '358';
        dump($vendor->fetchAccountDetail($data));
    }
}
