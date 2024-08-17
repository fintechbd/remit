<?php

namespace Fintech\Remit\Commands;

use Fintech\Business\Facades\Business;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Console\Command;

class IslamiBankSetupCommand extends Command
{
    const ID_DOC_TYPES = [
        'passport' => '1',
        'driving-licence' => '6',
        'national-identity-card' => '9',
        'residence-permit' => '7',
        'voter-id' => '7',
        'tax-id' => '7',
        'social-security-card' => '7',
        'postal-identity-card' => '7',
        'professional-qualification-card' => '7',
        'work-permit' => '7',
    ];

    public $signature = 'remit:islami-bank-setup';

    public $description = 'install/update required fields for islami bank';

    public function handle(): int
    {
        try {

            $this->updateIdDocType();

            $this->addServiceVendor();

            $this->info('Islami Bank Remit service vendor setup completed.');

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

            $idDocType = MetaData::idDocType()->list(['code' => $code])->first();

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

            if (MetaData::idDocType()->update($idDocType->getKey(), ['vendor_code' => $vendor_code])) {
                $this->line("ID Doc Type ID: {$idDocType->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('ID Doc Type metadata updated successfully.');
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__ . "/../../resources/img/service_vendor/";

        $vendor = [
            'service_vendor_name' => 'Islami Bank',
            'service_vendor_slug' => 'islamibank',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,' . base64_encode(file_get_contents("{$dir}/logo_png/islamibank.png")),
            'logo_svg' => 'data:image/svg+xml;base64,' . base64_encode(file_get_contents("{$dir}/logo_svg/islamibank.svg")),
            'enabled' => false,
        ];

        if (Business::serviceVendor()->list(['service_vendor_slug' => $vendor['service_vendor_slug']])->first()) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }
}
