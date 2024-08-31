<?php

namespace Fintech\Remit\Seeders;

use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Database\Seeder;

class RemitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Core::packageExists('Business')) {

            foreach ($this->serviceType() as $entry) {
                $serviceTypeChildren = $entry['serviceTypeChildren'] ?? [];

                if (isset($entry['serviceTypeChildren'])) {
                    unset($entry['serviceTypeChildren']);
                }

                $findServiceTypeModel = Business::serviceType()->list(['service_type_slug' => $entry['service_type_slug']])->first();
                if ($findServiceTypeModel) {
                    $serviceTypeModel = Business::serviceType()->update($findServiceTypeModel->id, $entry);
                } else {
                    $serviceTypeModel = Business::serviceType()->create($entry);
                }

                if (! empty($serviceTypeChildren)) {
                    array_walk($serviceTypeChildren, function ($item) use (&$serviceTypeModel) {
                        $item['service_type_parent_id'] = $serviceTypeModel->getKey();
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
        $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

        return [
            [
                'service_type_parent_id' => null,
                'service_type_name' => 'Money Transfer',
                'service_type_slug' => 'money_transfer',
                'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'money_transfer.svg')),
                'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'money_transfer.png')),
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'service_type_step' => '1',
                'enabled' => true,
                'serviceTypeChildren' => [
                    ['service_type_name' => 'Bank Transfer', 'service_type_slug' => 'bank_transfer', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'bank_transfer.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'bank_transfer.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
                    ['service_type_name' => 'Cash Pickup', 'service_type_slug' => 'cash_pickup', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'cash_pickup.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'cash_pickup.png')), 'service_type_is_parent' => 'no', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
                    ['service_type_name' => 'Wallet Transfer', 'service_type_slug' => 'wallet_transfer', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_transfer.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_transfer.png')), 'service_type_is_parent' => 'yes', 'service_type_is_description' => 'no', 'service_type_step' => '2', 'enabled' => true],
                ],
            ],
        ];
    }

    private function service(): array
    {
        $image_svg = __DIR__.'/../../resources/img/service/logo_svg/';
        $image_png = __DIR__.'/../../resources/img/service/logo_png/';
        $vendor_id = config('fintech.business.default_vendor', 1);

        return [
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'bank_transfer'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Bank Transfer', 'service_slug' => 'bank_transfer', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'bank_transfer.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'bank_transfer.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => '', 'account_number' => '', 'transactional_currency' => '', 'beneficiary_type_id' => 1, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'cash_pickup'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Cash Pickup', 'service_slug' => 'cash_pickup', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'cash_pickup.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'cash_pickup.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => '', 'account_number' => '', 'transactional_currency' => '', 'beneficiary_type_id' => 3, 'operator_short_code' => null], 'enabled' => true],
            ['service_type_id' => Business::serviceType()->list(['service_type_slug' => 'wallet_transfer'])->first()->id, 'service_vendor_id' => $vendor_id, 'service_name' => 'Wallet Transfer', 'service_slug' => 'wallet_transfer', 'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($image_svg.'wallet_transfer.svg')), 'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents($image_png.'wallet_transfer.png')), 'service_notification' => 'yes', 'service_delay' => 'yes', 'service_stat_policy' => 'yes', 'service_serial' => 1, 'service_data' => ['visible_website' => 'yes', 'visible_android_app' => 'yes', 'visible_ios_app' => 'yes', 'account_name' => '', 'account_number' => '', 'transactional_currency' => '', 'beneficiary_type_id' => 5, 'operator_short_code' => null], 'enabled' => true],
        ];

    }

    private function serviceStat(): array
    {
        $serviceLists = $this->service();
        $serviceStats = [];
        $roles = Auth::role()->list(['id_not_in' => [1]])->pluck('id')->toArray();
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
                            'commission' => '0',
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
