<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Fintech\Business\Facades\Business;
use Illuminate\Database\Seeder;

class ServiceVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach ($this->data() as $entry) {
            if ($entry['logo_png'] != null) {
                $image_png = __DIR__ . '/../../resources/img/service_vendor/logo_png/' . $entry['logo_png'];
                $entry['logo_png'] = 'data:image/png;base64,' . base64_encode(file_get_contents($image_png));
            }
            if ($entry['logo_svg'] != null) {
                $image_svg = __DIR__ . '/../../resources/img/service_vendor/logo_svg/' . $entry['logo_svg'];
                $entry['logo_svg'] = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($image_svg));
            }
            Business::serviceVendor()->create($entry);
        }
    }

    private function data()
    {
        return [
            [
                'service_vendor_name' => 'Agrani Bank',
                'service_vendor_slug' => 'agrani',
                'service_vendor_data' => [],
                'logo_svg' => 'agrani.svg',
                'logo_png' => 'agrani.png',
                'enabled' => false,
            ],
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
            ]
        ];
    }
}
