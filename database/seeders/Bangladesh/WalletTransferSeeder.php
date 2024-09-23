<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class WalletTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            $parent = Business::serviceType()->findWhere(['service_type_slug' => 'wallet_transfer']);

            $country = MetaData::country()->findWhere(['iso2' => 'BD'])->id;

            $walletTransferId = (int) (Banco::beneficiaryType()->findWhere(['slug' => 'wallet-transfer'])?->id ?? 1);

            foreach ($this->data() as $entry) {
                Business::serviceTypeManager($entry, $parent)
                    ->hasService()
                    ->distCountries([$country])
                    ->serviceSettings([
                        'transactional_currency' => 'BDT',
                        'beneficiary_type_id' => (int)$walletTransferId,
                    ])
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
                'service_type_name' => 'bKash',
                'service_type_slug' => 'mfs_bkash',
                'logo_svg' => $image_svg.'mfs_bkash.svg',
                'logo_png' => $image_png.'mfs_bkash.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'Nagad',
                'service_type_slug' => 'mfs_nagad',
                'logo_svg' => $image_svg.'mfs_nagad.svg',
                'logo_png' => $image_png.'mfs_nagad.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'Rocket',
                'service_type_slug' => 'mbs_rocket',
                'logo_svg' => $image_svg.'mbs_rocket.svg',
                'logo_png' => $image_png.'mbs_rocket.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'MCash',
                'service_type_slug' => 'mbs_m_cash',
                'logo_svg' => $image_svg.'mbs_m_cash.svg',
                'logo_png' => $image_png.'mbs_m_cash.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'SureCash',
                'service_type_slug' => 'mbs_sure_cash',
                'logo_svg' => $image_svg.'mbs_sure_cash.svg',
                'logo_png' => $image_png.'mbs_sure_cash.png',
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'Upay',
                'service_type_slug' => 'mbs_u_pay',
                'logo_svg' => $image_svg.'mbs_u_pay.svg',
                'logo_png' => $image_png.'mbs_u_pay.png', 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'Ipay',
                'service_type_slug' => 'mbs_i_pay',
                'logo_svg' => $image_svg.'mbs_i_pay.svg',
                'logo_png' => $image_png.'mbs_i_pay.png', 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'Trust Axiata Pay (Tap)',
                'service_type_slug' => 'mbs_trust_axiata_pay_tap',
                'logo_svg' => $image_svg.'mbs_trust_axiata_pay_tap.svg',
                'logo_png' => $image_png.'mbs_trust_axiata_pay_tap.png', 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
            [
                'service_type_name' => 'OK Wallet',
                'service_type_slug' => 'mbs_ok_wallet',
                'logo_svg' => $image_svg.'mbs_ok_wallet.svg',
                'logo_png' => $image_png.'mbs_ok_wallet.png', 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
            ],
        ];
    }
}
