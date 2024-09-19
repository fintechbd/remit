<?php

namespace Fintech\Remit\Commands;

use Fintech\Banco\Facades\Banco;
use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\MetaData\Facades\MetaData;
use Illuminate\Console\Command;
use Throwable;

class MeghnaBankSetupCommand extends Command
{
    const ID_DOC_TYPES = [
        'passport' => '2',
        'driving-licence' => '3',
        'national-identity-card' => '1',
        'residence-permit' => '8',
        'voter-id' => '8',
        'tax-id' => '8',
        'social-security-card' => '4',
        'postal-identity-card' => '8',
        'professional-qualification-card' => '7',
        'work-permit' => '7',
    ];

    const BD_BANKS = [
        'agrani-bank-ltd' => '2',
        'woori-bank' => '55',
        'trust-bank-limited' => '51',
        'first-security-islami-bank-limited' => '17',
        'premier-bank-limited' => '50',
        'rajshahi-krishi-unnayan-bank' => '58',
        'dhaka-bank-limited' => '13',
        'dutch-bangla-bank-limited' => '14',
        'city-bank-limited' => '48',
        'hongkong-shanghai-banking-corp-limited' => '19',
        'modhumoti-bank-limited' => '28',
        'nrb-commercial-bank-limited' => '34',
        'sbac-bank-limited' => '40',
        'eastern-bank-limited' => '15',
        'one-bank-limited' => '36',
        'commercial-bank-of-ceylon-limited' => '12',
        'bank-asia-limited' => '6',
        'rupali-bank-limited' => '39',
        'midland-bank-limited' => '27',
        'bangladesh-samabaya-bank-limited' => '59',
        'shimanto-bank-limited' => '60',
        'prime-bank-limited' => '37',
        'ific-bank-limited' => '21',
        'jamuna-bank-limited' => '23',
        'sonali-bank-limited' => '43',
        'bank-al-falah-limited' => '5',
        'meghna-bank-limited' => '25',
        'state-bank-of-india' => '47',
        'mercantile-bank-limited' => '26',
        'habib-bank-limited' => '18',
        'pubali-bank-limited' => '38',
        'basic-bank-limited' => '7',
        'nrb-bank-limited' => '33',
        'icb-islamic-bank-limited' => '20',
        'exim-bank-limited' => '16',
        'union-bank-limited' => '52',
        'janata-bank-limited' => '24',
        'united-commercial-bank-limited' => '53',
        'mutual-trust-bank-limited' => '29',
        'standard-chartered-bank' => '46',
        'al-arafah-islami-bank-limited' => '3',
        'bangladesh-development-bank-limited' => '9',
        'islami-bank-bangladesh-limited' => '22',
        'southeast-bank-limited' => '44',
        'shahjalal-islami-bank-limited' => '41',
        'brac-bank-limited' => '10',
        'national-bank-limited' => '30',
        'ab-bank-limited' => '1',
        'national-bank-of-pakistan' => '31',
        'social-islami-bank-ltd' => '42',
        'community-bank-bangladesh-limited' => '62',
        'citibank' => '11',
        'standard-bank-limited' => '45',
        'ncc-bank-limited' => '32',
        'bangladesh-krishi-bank' => '4',
        'bangladesh-commerce-bank-limited' => '8',
        'nrb-global-bank-limited' => '35',
        'uttara-bank-limited' => '54',
        'bangladesh-bank' => '61',
        'bengal-commercial-bank-ltd' => '',
        'padma-bank-limited' => '',
    ];

    public $signature = 'remit:meghna-bank-setup';

    public $description = 'install/update required fields for meghna bank';

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

            if (Core::packageExists('Banco')) {
                $this->updateBank();
            } else {
                $this->info('`fintech/banco` is not installed. Skipped');
            }

            $this->info('Meghna Bank Remit service vendor setup completed.');

            return self::SUCCESS;

        } catch (Throwable $th) {

            $this->error($th->getMessage());

            return self::FAILURE;
        }
    }

    private function updateIdDocType(): void
    {

        $bar = $this->output->createProgressBar(count(self::ID_DOC_TYPES));

        $bar->start();

        foreach (self::ID_DOC_TYPES as $code => $name) {

            $idDocType = MetaData::idDocType()->findWhere(['code' => $code]);

            if (! $idDocType) {
                continue;
            }

            $vendor_code = $idDocType->vendor_code;

            if ($vendor_code == null) {
                $vendor_code = [];
            }

            if (is_string($vendor_code)) {
                $vendor_code = json_decode($vendor_code, true);
            }

            $vendor_code['remit']['meghnabank'] = $name;

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
        $dir = __DIR__.'/../../resources/img/service_vendor';

        $vendor = [
            'service_vendor_name' => 'Meghna Bank',
            'service_vendor_slug' => 'meghnabank',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents("{$dir}/logo_png/meghnabank.png")),
            'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents("{$dir}/logo_svg/meghnabank.svg")),
            'enabled' => false,
        ];

        if (Business::serviceVendor()->findWhere(['service_vendor_slug' => $vendor['service_vendor_slug']])) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }

    private function updateBank(): void
    {

        $bar = $this->output->createProgressBar(count(self::BD_BANKS));

        $bar->start();

        foreach (self::BD_BANKS as $code => $name) {

            $bank = Banco::bank()->findWhere(['slug' => $code]);

            if (! $bank) {
                continue;
            }

            $vendor_code = $bank->vendor_code;

            if ($vendor_code == null) {
                $vendor_code = [];
            }

            if (is_string($vendor_code)) {
                $vendor_code = json_decode($vendor_code, true);
            }

            $vendor_code['remit']['meghnabank'] = $name;

            if (Banco::bank()->update($bank->getKey(), ['vendor_code' => $vendor_code])) {
                $this->info("Bank ID: {$bank->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('Bank updated successfully.');
    }
}
