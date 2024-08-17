<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Fintech\Business\Facades\Business;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ServiceVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('remit:agrani-bank-setup');
        Artisan::call('remit:city-bank-setup');
        Artisan::call('remit:islami-bank-setup');
        Artisan::call('remit:meghna-bank-setup');
    }

    private function data()
    {
        return [
            [
                'service_vendor_name' => 'City Bank',
                'service_vendor_slug' => 'citybank',
                'service_vendor_data' => [],
                'logo_svg' => 'citybank.svg',
                'logo_png' => 'citybank.png',
                'enabled' => false,
            ],
            [
                'service_vendor_name' => 'Islami Bank',
                'service_vendor_slug' => 'islamibank',
                'service_vendor_data' => [],
                'logo_svg' => 'islamibank.svg',
                'logo_png' => 'islamibank.png',
                'enabled' => false,
            ],
            [
                'service_vendor_name' => 'Meghna Bank',
                'service_vendor_slug' => 'meghnabank',
                'service_vendor_data' => [],
                'logo_svg' => 'meghnabank.svg',
                'logo_png' => 'meghnabank.png',
                'enabled' => false,
            ],
        ];
    }
}
