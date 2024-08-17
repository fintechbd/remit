<?php

namespace Fintech\Remit\Seeders\Bangladesh;

use Fintech\Auth\Facades\Auth;
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

            foreach ($this->serviceType() as $entry) {
                $serviceTypeChild = $entry['serviceTypeChild'] ?? [];

                if (isset($entry['serviceTypeChild'])) {
                    unset($entry['serviceTypeChild']);
                }

                $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();
                if ($findServiceTypeModel) {
                    $serviceTypeModel = Business::serviceType()->update($findServiceTypeModel->id, $entry);
                } else {
                    $serviceTypeModel = Business::serviceType()->create($entry);
                }

                if (! empty($serviceTypeChild)) {
                    array_walk($serviceTypeChild, function ($item) use (&$serviceTypeModel) {
                        $item['service_type_parent_id'] = $serviceTypeModel->id;
                        Business::serviceType()->create($item);
                    });
                }
            }

            $serviceData = $this->service();

            foreach (array_chunk($serviceData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::service()->create($entry);
                }
            }

            $serviceStatData = $this->serviceStat();
            foreach (array_chunk($serviceStatData, 200) as $block) {
                set_time_limit(2100);
                foreach ($block as $entry) {
                    Business::serviceStat()->customStore($entry);
                }
            }
        }
    }

    private function serviceType(): array
    {
        $image_svg = __DIR__.'/../../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'bKash',
                'service_type_slug' => 'mfs_bkash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mfs_bkash.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mfs_bkash.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'Nagad',
                'service_type_slug' => 'mfs_nagad',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mfs_nagad.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mfs_nagad.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'Rocket',
                'service_type_slug' => 'mbs_rocket',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_rocket.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_rocket.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'MCash',
                'service_type_slug' => 'mbs_m_cash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_m_cash.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_m_cash.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'SureCash',
                'service_type_slug' => 'mbs_sure_cash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_sure_cash.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_sure_cash.png')),
                'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'Upay',
                'service_type_slug' => 'mbs_u_pay',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_u_pay.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_u_pay.png')), 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'Ipay',
                'service_type_slug' => 'mbs_i_pay',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_i_pay.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_i_pay.png')), 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'Trust Axiata Pay (Tap)',
                'service_type_slug' => 'mbs_trust_axiata_pay_tap',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_trust_axiata_pay_tap.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_trust_axiata_pay_tap.png')), 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
            [
                'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id,
                'service_type_name' => 'OK Wallet',
                'service_type_slug' => 'mbs_ok_wallet',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_ok_wallet.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_ok_wallet.png')), 'service_type_is_parent' => 'no',
                'service_type_is_description' => 'no',
                'service_type_step' => '3',
                'enabled' => true],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../../resources/img/service/logo_png/';
        $vendor_id = config('fintech.business.default_vendor', 1);

        return [
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mfs_bkash'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'bKash',
                'service_slug' => 'mfs_bkash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mfs_bkash.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mfs_bkash.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 1, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mfs_nagad'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Nagad',
                'service_slug' => 'mfs_nagad',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mfs_nagad.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mfs_nagad.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 3, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_rocket'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Rocket',
                'service_slug' => 'mbs_rocket',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_rocket.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_rocket.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_m_cash'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'MCash',
                'service_slug' => 'mbs_m_cash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_m_cash.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_m_cash.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_sure_cash'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'SureCash',
                'service_slug' => 'mbs_sure_cash',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_sure_cash.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_sure_cash.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_u_pay'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Upay',
                'service_slug' => 'mbs_u_pay',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_u_pay.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_u_pay.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_i_pay'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Ipay',
                'service_slug' => 'mbs_i_pay',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_i_pay.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_i_pay.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_trust_axiata_pay_tap'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Trust Axiata Pay (Tap)',
                'service_slug' => 'mbs_trust_axiata_pay_tap',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_trust_axiata_pay_tap.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_trust_axiata_pay_tap.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
            [
                'service_type_id' => Business::serviceType()->list(['service_type_slug' => 'mbs_ok_wallet'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'OK Wallet',
                'service_slug' => 'mbs_ok_wallet',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'mbs_ok_wallet.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'mbs_ok_wallet.png')), 'service_notification' => 'yes',
                'service_delay' => 'yes',
                'service_stat_policy' => 'yes',
                'service_serial' => 1,
                'service_data' => ['visible_website' => 'yes',
                    'visible_android_app' => 'yes',
                    'visible_ios_app' => 'yes',
                    'account_name' => '',
                    'account_number' => '',
                    'transactional_currency' => '',
                    'beneficiary_type_id' => 5, 'operator_short_code' => null],
                'enabled' => true,
            ],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
        $serviceStats = [];
        $roles = Auth::role()->list(['id_not_in_array' => [1]])->pluck('id')->toArray();
        $source_countries = MetaData::country()->list(['is_serving' => true])->pluck('id')->toArray();
        if (! empty($roles) && ! empty($source_countries)) {
            foreach ($serviceLists as $serviceList) {
                $service = Business::service()->list(['service_slug' => $serviceList['service_slug']])->first();
                $serviceStats[] = [
                    'role_id' => $roles,
                    'service_id' => $service->getKey(),
                    'service_slug' => $service->service_slug,
                    'source_country_id' => $source_countries,
                    'destination_country_id' => [19, 39, 101, 132, 133, 167, 192, 231],
                    'service_vendor_id' => config('fintech.business.default_vendor', 1),
                    'service_stat_data' => [
                        [
                            'lower_limit' => '10.00',
                            'higher_limit' => '5000.00',
                            'local_currency_higher_limit' => '25000.00',
                            'charge' => mt_rand(1, 7).'%',
                            'discount' => mt_rand(1, 7).'%',
                            'commission' => mt_rand(1, 7).'%',
                            'cost' => '0.00',
                            'charge_refund' => 'yes',
                            'discount_refund' => 'yes',
                            'commission_refund' => 'yes',
                        ],
                    ],
                    'enabled' => true,
                ];
            }
        }

        return $serviceStats;

    }
}
