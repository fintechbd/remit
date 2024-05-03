<?php

namespace Fintech\Remit\Http\Controllers;

use Fintech\Remit\Vendors\IslamiBankApi;
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
        $data['account_number'] = '';
        $data['account_type'] = '';
        $data['branch_code'] = '';
        dump($vendor->fetchAccountDetail($data));
    }
    public function islamiBankFetchRemittanceStatus(): void
    {
        $vendor = app()->make(\Fintech\Remit\Vendors\IslamiBankApi::class);
        $data['transaction_reference_number'] = '';
        $data['secret_key'] = '';
        dump($vendor->fetchRemittanceStatus($data));
    }
}
