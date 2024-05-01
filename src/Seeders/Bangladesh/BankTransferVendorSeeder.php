<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class BankTransferVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {
            $image_svg = __DIR__.'/../../../resources/img/service_vendor/logo_svg/';
            $image_png = __DIR__.'/../../../resources/img/service_vendor/logo_png/';

            foreach ($this->data() as $entry) {
                $entry['logo_svg'] = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'/'.$entry['logo_svg']));
                $entry['logo_png'] = 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'/'.$entry['logo_png']));
                Business::serviceVendor()->create($entry);
            }
        }
    }

    private function data(): array
    {
        return [
            [
                'service_vendor_name' => 'City Bank',
                'service_vendor_slug' => 'citybank',
                'service_vendor_data' => [],
                'enabled' => false,
                'logo_png' => 'citybank.png',
                'logo_svg' => 'citybank.svg',
            ],
            [
                'service_vendor_name' => 'Agrani Bank',
                'service_vendor_slug' => 'agrani',
                'service_vendor_data' => [],
                'enabled' => false,
                'logo_png' => 'agrani.png',
                'logo_svg' => 'agrani.svg',
            ],
            [
                'service_vendor_name' => 'EMQ',
                'service_vendor_slug' => 'emqapi',
                'service_vendor_data' => [],
                'enabled' => false,
                'logo_png' => 'emqapi.png',
                'logo_svg' => 'emqapi.svg',
            ],
            [
                'service_vendor_name' => 'Transfast',
                'service_vendor_slug' => 'transfast',
                'service_vendor_data' => [],
                'enabled' => false,
                'logo_png' => 'transfast.png',
                'logo_svg' => 'transfast.svg',
            ],
            [
                'service_vendor_name' => 'Value You',
                'service_vendor_slug' => 'valyou',
                'service_vendor_data' => [],
                'enabled' => false,
                'logo_png' => 'valyou.png',
                'logo_svg' => 'valyou.svg',
            ],

        ];
    }
}
