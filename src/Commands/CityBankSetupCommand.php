<?php

namespace Fintech\Remit\Commands;

use Fintech\Core\Facades\Core;
use Illuminate\Console\Command;

class CityBankSetupCommand extends Command
{
    const ID_DOC_TYPES = [
        'passport' => '1',
        'driving-licence' => '9',
        'national-identity-card' => '3',
        'residence-permit' => '5',
        'voter-id' => '9',
        'tax-id' => '9',
        'social-security-card' => '4',
        'postal-identity-card' => '9',
        'professional-qualification-card' => '9',
        'work-permit' => '2',
    ];

    public $signature = 'remit:city-bank-setup';

    public $description = 'install/update required fields for city bank';

    public function handle(): int
    {
        try {

            if (Core::packageExists('MetaData')) {
                $this->updateIdDocType();
            } else {
                $this->info('`fintech/metadata` is not installed. Skipped');
            }

            if (Core::packageExists('Business')) {
                $this->addServiceVendor();
            } else {
                $this->info('`fintech/business` is not installed. Skipped');
            }

            $this->info('City Bank Remit service vendor setup completed.');

            return self::SUCCESS;

        } catch (\Throwable $th) {

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    private function updateIdDocType(): void
    {

        $bar = $this->output->createProgressBar(count(self::ID_DOC_TYPES));

        $bar->start();

        foreach (self::ID_DOC_TYPES as $code => $name) {

            $idDocType = \Fintech\MetaData\Facades\MetaData::idDocType()->list(['code' => $code])->first();

            if (!$idDocType) {
                continue;
            }

            $vendor_code = $idDocType->vendor_code;

            if ($vendor_code == null) {
                $vendor_code = [];
            }

            if (is_string($vendor_code)) {
                $vendor_code = json_decode($vendor_code, true);
            }

            $vendor_code['remit']['citybank'] = $name;

            if (\Fintech\MetaData\Facades\MetaData::idDocType()->update($idDocType->getKey(), ['vendor_code' => $vendor_code])) {
                $this->line("ID Doc Type ID: {$idDocType->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('ID Doc Type metadata updated successfully.');
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__ . '/../../resources/img/service_vendor/';

        $vendor = [
            'service_vendor_name' => 'City Bank',
            'service_vendor_slug' => 'citybank',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,' . base64_encode(file_get_contents("{$dir}/logo_png/citybank.png")),
            'logo_svg' => 'data:image/svg+xml;base64,' . base64_encode(file_get_contents("{$dir}/logo_svg/citybank.svg")),
            'enabled' => false,
        ];

        if (\Fintech\Business\Facades\Business::serviceVendor()->list(['service_vendor_slug' => $vendor['service_vendor_slug']])->first()) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            \Fintech\Business\Facades\Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }
}
