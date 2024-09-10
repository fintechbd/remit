<?php

namespace Fintech\Remit\Seeders;

use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Illuminate\Database\Seeder;

class RemitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(...$countries): void
    {
        if (Core::packageExists('Business')) {

            $parent = Business::serviceType()->list(['service_type_slug' => 'money_transfer'])->first();

            foreach ($this->data() as $entry) {
                Business::serviceTypeManager($entry, $parent)
                    ->hasService()
                    ->distCountries($countries)
                    ->enabled()
                    ->execute();
            }
        }
    }

    private function data(): array
    {
        $image_svg = base_path('vendor/fintech/remit/resources/img/service_type/logo_svg/');
        $image_png = base_path('vendor/fintech/remit/resources/img/service_type/logo_png/');

        return [
            [
                'service_type_name' => 'Bank Transfer',
                'service_type_slug' => 'bank_transfer',
                'logo_svg' => "{$image_svg}bank_transfer.svg",
                'logo_png' => "{$image_png}bank_transfer.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_settings' => [
                    'beneficiary_type_id' => Banco::beneficiaryType()->list(['slug' => 'bank-transfer'])->first()?->id ?? 1,
                ],
            ],
            [
                'service_type_name' => 'Cash Pickup',
                'service_type_slug' => 'cash_pickup',
                'logo_svg' => "{$image_svg}cash_pickup.svg",
                'logo_png' => "{$image_png}cash_pickup.png",
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_settings' => [
                    'beneficiary_type_id' => Banco::beneficiaryType()->list(['slug' => 'cash-pickup'])->first()?->id ?? 1,
                ],
            ],
            [
                'service_type_name' => 'Wallet Transfer',
                'service_type_slug' => 'wallet_transfer',
                'logo_svg' => "{$image_svg}wallet_transfer.svg",
                'logo_png' => "{$image_png}wallet_transfer.png",
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
            ],
        ];
    }
}
