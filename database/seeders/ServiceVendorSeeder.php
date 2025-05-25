<?php

namespace Fintech\Remit\Seeders;

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
                $image_png = __DIR__.'/../../resources/img/service_vendor/logo_png/'.$entry['logo_png'];
                $entry['logo_png'] = 'data:image/png;base64,'.base64_encode(file_get_contents($image_png));
            }
            if ($entry['logo_svg'] != null) {
                $image_svg = __DIR__.'/../../resources/img/service_vendor/logo_svg/'.$entry['logo_svg'];
                $entry['logo_svg'] = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg));
            }
            business()->serviceVendor()->create($entry);
        }
    }

    private function data()
    {
        return [
            [
                'service_vendor_name' => 'EMQ',
                'service_vendor_slug' => 'emqapi',
                'service_vendor_data' => [],
                'logo_svg' => 'emqapi.svg',
                'logo_png' => 'emqapi.png',
                'enabled' => false,
            ],
            [
                'service_vendor_name' => 'TransFast',
                'service_vendor_slug' => 'transfast',
                'service_vendor_data' => [],
                'logo_svg' => 'transfast.svg',
                'logo_png' => 'transfast.png',
                'enabled' => false,
            ],
            [
                'service_vendor_name' => 'Valyou',
                'service_vendor_slug' => 'valyou',
                'service_vendor_data' => [],
                'logo_svg' => 'valyou.svg',
                'logo_png' => 'valyou.png',
                'enabled' => false,
            ],
        ];
    }
}
