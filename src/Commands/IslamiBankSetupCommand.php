<?php

namespace Fintech\Remit\Commands;

use Fintech\Core\Facades\Core;
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

    const BD_BANKS = [
        'agrani-bank-ltd' => '11',
        'woori-bank' => '81',
        'trust-bank-limited' => '69',
        'first-security-islami-bank-limited' => '67',
        'premier-bank-limited' => '66',
        'rajshahi-krishi-unnayan-bank' => '',
        'dhaka-bank-limited' => '56',
        'dutch-bangla-bank-limited' => '59',
        'city-bank-limited' => '44',
        'hongkong-shanghai-banking-corp-limited' => '83',
        'modhumoti-bank-limited' => '295',
        'nrb-commercial-bank-limited' => '260',
        'sbac-bank-limited' => '270',
        'eastern-bank-limited' => '52',
        'one-bank-limited' => '62',
        'commercial-bank-of-ceylon-limited' => '27',
        'bank-asia-limited' => '68',
        'rupali-bank-limited' => '14',
        'midland-bank-limited' => '285',
        'bangladesh-samabaya-bank-limited' => '',
        'shimanto-bank-limited' => '',
        'prime-bank-limited' => '54',
        'ific-bank-limited' => '45',
        'jamuna-bank-limited' => '71',
        'sonali-bank-limited' => '15',
        'bank-al-falah-limited' => '84',
        'meghna-bank-limited' => '275',
        'state-bank-of-india' => '24',
        'mercantile-bank-limited' => '60',
        'habib-bank-limited' => '25',
        'pubali-bank-limited' => '47',
        'basic-bank-limited' => '51',
        'nrb-bank-limited' => '290',
        'icb-islamic-bank-limited' => '49',
        'exim-bank-limited' => '63',
        'union-bank-limited' => '265',
        'janata-bank-limited' => '12',
        'united-commercial-bank-limited' => '46',
        'mutual-trust-bank-limited' => '65',
        'standard-chartered-bank' => '',
        'al-arafah-islami-bank-limited' => '57',
        'bangladesh-development-bank-limited' => '86',
        'bengal-commercial-bank-ltd' => '',
        'padma-bank-limited' => '280',
        'islami-bank-bangladesh-limited' => '42',
        'southeast-bank-limited' => '55',
        'shahjalal-islami-bank-limited' => '70',
        'brac-bank-limited' => '72',
        'national-bank-limited' => '43',
        'ab-bank-limited' => '41',
        'national-bank-of-pakistan' => '28',
        'social-islami-bank-ltd' => '58',
        'community-bank-bangladesh-limited' => '',
        'citibank' => '',
        'standard-bank-limited' => '61',
        'ncc-bank-limited' => '53',
        'bangladesh-krishi-bank' => '31',
        'bangladesh-commerce-bank-limited' => '64',
        'nrb-global-bank-limited' => '',
        'uttara-bank-limited' => '48',
        'bangladesh-bank' => '10',
    ];

    const ISLAMI_BRANCHES = [
        125010070 => '180',
        125010467 => '401',
        125010946 => '150',
        125011037 => '241',
        125011303 => '374',
        125030139 => '297',
        125040132 => '234',
        125060220 => '392',
        125060288 => '111',
        125062202 => '271',
        125062602 => '318',
        125090108 => '174',
        125090203 => '019',
        125090229 => '353',
        125090708 => '368',
        125100377 => '112',
        125100498 => '312',
        125100919 => '245',
        125101392 => '173',
        125101550 => '015',
        125101792 => '007',
        125102038 => '325',
        125102425 => '013',
        125120052 => '018',
        125120102 => '176',
        125120252 => '270',
        125120436 => '196',
        125121035 => '344',
        125121369 => '277',
        125130118 => '359',
        125130318 => '134',
        125130671 => '255',
        125130884 => '189',
        125131188 => '305',
        125150130 => '103',
        125150464 => '124',
        125150527 => '367',
        125150606 => '406',
        125150798 => '348',
        125150943 => '239',
        125150972 => '329',
        125151092 => '252',
        125151139 => '017',
        125151300 => '371',
        125151489 => '193',
        125151755 => '146',
        125151818 => '382',
        125151939 => '162',
        125152446 => '288',
        125152741 => '268',
        125153166 => '334',
        125153229 => '170',
        125153645 => '137',
        125153737 => '361',
        125153911 => '380',
        125154181 => '294',
        125154273 => '106',
        125154660 => '156',
        125155069 => '326',
        125155627 => '298',
        125155801 => '304',
        125155872 => '372',
        125155922 => '202',
        125156163 => '163',
        125156497 => '313',
        125156918 => '266',
        125157067 => '405',
        125157391 => '221',
        125157517 => '167',
        125180047 => '402',
        125180197 => '230',
        125180584 => '323',
        125190408 => '346',
        125190824 => '023',
        125190882 => '314',
        125191065 => '237',
        125191157 => '121',
        125191423 => '247',
        125191528 => '014',
        125192114 => '258',
        125192714 => '139',
        125197643 => '341',
        125220057 => '366',
        125220194 => '141',
        125220231 => '005',
        125220257 => '116',
        125220402 => '227',
        125220778 => '330',
        125220857 => '350',
        125220910 => '147',
        125260088 => '383',
        125260138 => '151',
        125260183 => '410',
        125260341 => '310',
        125260433 => '391',
        125260509 => '404',
        125260525 => '342',
        125260738 => '203',
        125261182 => '205',
        125261337 => '215',
        125261458 => '136',
        125261632 => '283',
        125261724 => '177',
        125261753 => '276',
        125261995 => '218',
        125262149 => '224',
        125262457 => '016',
        125262536 => '222',
        125262981 => '131',
        125263106 => '210',
        125263193 => '191',
        125263377 => '267',
        125263522 => '129',
        125263580 => '274',
        125263614 => '290',
        125264097 => '130',
        125264305 => '209',
        125264639 => '207',
        125264826 => '010',
        125270007 => '100',
        125270344 => '379',
        125270652 => '002',
        125270881 => '179',
        125271301 => '105',
        125271422 => '396',
        125271848 => '375',
        125272050 => '289',
        125272326 => '109',
        125272447 => '337',
        125272689 => '213',
        125272984 => '110',
        125273220 => '204',
        125273583 => '364',
        125273675 => '240',
        125273820 => '328',
        125273888 => '102',
        125274182 => '332',
        125274245 => '311',
        125274395 => '145',
        125274690 => '233',
        125274753 => '118',
        125275202 => '206',
        125275686 => '157',
        125275749 => '226',
        125275923 => '197',
        125276522 => '259',
        125276856 => '223',
        125277042 => '351',
        125277097 => '127',
        125280347 => '238',
        125280671 => '138',
        125282174 => '347',
        125290290 => '320',
        125290524 => '148',
        125300319 => '285',
        125300348 => '339',
        125300377 => '295',
        125300522 => '122',
        125301284 => '021',
        125301484 => '249',
        125320528 => '185',
        125320586 => '219',
        125321301 => '302',
        125330226 => '324',
        125330550 => '158',
        125330592 => '356',
        125330884 => '376',
        125330947 => '338',
        125331038 => '296',
        125331638 => '216',
        125350372 => '208',
        125351092 => '400',
        125360612 => '198',
        125361103 => '327',
        125361408 => '009',
        125380405 => '153',
        125380676 => '369',
        125390103 => '331',
        125390853 => '161',
        125391694 => '178',
        125410283 => '164',
        125410559 => '275',
        125410946 => '125',
        125411095 => '160',
        125411211 => '388',
        125411637 => '135',
        125420310 => '183',
        125420552 => '393',
        125440358 => '003',
        125440640 => '175',
        125440790 => '287',
        125441007 => '300',
        125460075 => '301',
        125470702 => '182',
        125471543 => '107',
        125472089 => '232',
        125472155 => '011',
        125480192 => '228',
        125480589 => '343',
        125480671 => '120',
        125490108 => '355',
        125490403 => '195',
        125490645 => '362',
        125500885 => '236',
        125500948 => '133',
        125501363 => '265',
        125510091 => '370',
        125510196 => '335',
        125510738 => '190',
        125510970 => '235',
        125511032 => '254',
        125520465 => '253',
        125520599 => '322',
        125540140 => '006',
        125540287 => '378',
        125540403 => '217',
        125540766 => '269',
        125550556 => '246',
        125560612 => '181',
        125560791 => '397',
        125560825 => '385',
        125570378 => '262',
        125580100 => '284',
        125580942 => '256',
        125581183 => '114',
        125581725 => '214',
        125591036 => '187',
        125591423 => '307',
        125591665 => '399',
        125610317 => '398',
        125610946 => '303',
        125611703 => '012',
        125611758 => '140',
        125612357 => '345',
        125641007 => '389',
        125641094 => '308',
        125641186 => '144',
        125641249 => '155',
        125641757 => '349',
        125650469 => '394',
        125650643 => '263',
        125670049 => '352',
        125670223 => '280',
        125670528 => '363',
        125670823 => '292',
        125671185 => '108',
        125671277 => '340',
        125671701 => '020',
        125680671 => '171',
        125680734 => '377',
        125680855 => '119',
        125680918 => '172',
        125681096 => '395',
        125690311 => '286',
        125691099 => '184',
        125700256 => '132',
        125700885 => '264',
        125700948 => '211',
        125720731 => '260',
        125730468 => '257',
        125730734 => '212',
        125730792 => '152',
        125750222 => '261',
        125750251 => '168',
        125750448 => '004',
        125750585 => '273',
        125750677 => '126',
        125751571 => '225',
        125752088 => '291',
        125752233 => '360',
        125752354 => '354',
        125760641 => '403',
        125761211 => '229',
        125761332 => '165',
        125761787 => '115',
        125762052 => '244',
        125762265 => '022',
        125770194 => '387',
        125770552 => '243',
        125780526 => '386',
        125781091 => '192',
        125790132 => '306',
        125790529 => '408',
        125790611 => '281',
        125790761 => '200',
        125810225 => '319',
        125810346 => '381',
        125811079 => '008',
        125811637 => '279',
        125811932 => '113',
        125820673 => '390',
        125820736 => '242',
        125840529 => '201',
        125850522 => '333',
        125851363 => '384',
        125851455 => '117',
        125860196 => '282',
        125860583 => '357',
        125860675 => '251',
        125870586 => '169',
        125870610 => '186',
        125871093 => '143',
        125871219 => '336',
        125880226 => '278',
        125881870 => '149',
        125881904 => '154',
        125882237 => '317',
        125890553 => '188',
        125900227 => '231',
        125900498 => '272',
        125901121 => '293',
        125910046 => '166',
        125910312 => '128',
        125910433 => '142',
        125910954 => '321',
        125911540 => '220',
        125911603 => '365',
        125912086 => '248',
        125912507 => '199',
        125913551 => '104',
        125914150 => '309',
        125930118 => '001',
        125931483 => '250',
        125931517 => '373',
        125932295 => '159',
        125940979 => '194',
        125270786 => '123',
        125471544 => '316',
        125702988 => '407',
        125191210 => '409',
        125940829 => '411',
        125910204 => '412',
        125610175 => '413',
        125691486 => '414',
        125261029 => '415',
        125270599 => '416',
        125290340 => '417',
        125062299 => '418',
        125220060 => '419',
        125280376 => '420',
        125190495 => '421',
        125156400 => '422',
        125752262 => '423',
        125156042 => '424',
        125890340 => '425',
        125881841 => '426',
        125474199 => '427',
        125591544 => '428',
        125190574 => '429',
        125270852 => '430',
        125550527 => '431',
        125270636 => '432',
        125061458 => '433',
        125470223 => '434',
        125330413 => '435',
        125150998 => '436',
        125510820 => '437',
        125155593 => '438',
        125030197 => '439',
        125500285 => '440',
        125154365 => '441',
        125380463 => '442',
        125260220 => '443',
        125152567 => '444',
        125810612 => '445',
        125480400 => '446',
        125350730 => '447',
        125193250 => '448',
        125150327 => '449',
        125157638 => '450',
        125153074 => '451',
        125760467 => '452',
        125150569 => '453',
        125151050 => '454',
        125320052 => '455',
        125720678 => '456',
        125321093 => '457',
        125300614 => '458',
        125441094 => '459',
        125291099 => '460',
        125281870 => '461',
        125330792 => '462',
        125273462 => '463',
        125932208 => '464',
        125810104 => '465',
        125156468 => '466',
        125681304 => '467',
        125540708 => '468',
        125470852 => '469',
        125151142 => '470',
        125151168 => '471',
        125121606 => '472',
        125360346 => '473',
        125690287 => '474',
        125100885 => '475',
        125390761 => '476',
    ];

    public $signature = 'remit:islami-bank-setup';

    public $description = 'install/update required fields for islami bank';

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
                $this->updateBranches();
                $this->addBeneficiaryAccountTypeCodes();
            } else {
                $this->info('`fintech/banco` is not installed. Skipped');
            }

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

            $idDocType = \Fintech\MetaData\Facades\MetaData::idDocType()->list(['code' => $code])->first();

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

            $vendor_code['remit']['islamibank'] = $name;

            if (\Fintech\MetaData\Facades\MetaData::idDocType()->update($idDocType->getKey(), ['vendor_code' => $vendor_code])) {
                $this->line("ID Doc Type ID: {$idDocType->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('ID Doc Type metadata updated successfully.');
    }

    private function updateBank(): void
    {

        $bar = $this->output->createProgressBar(count(self::BD_BANKS));

        $bar->start();

        foreach (self::BD_BANKS as $code => $name) {

            $bank = \Fintech\Banco\Facades\Banco::bank()->list(['slug' => $code])->first();

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

            $vendor_code['remit']['islamibank'] = $name;

            if (\Fintech\Banco\Facades\Banco::bank()->update($bank->getKey(), ['vendor_code' => $vendor_code])) {
                $this->line("Bank ID: {$bank->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('Bank updated successfully.');
    }

    private function updateBranches(): void
    {

        $bar = $this->output->createProgressBar(count(self::ISLAMI_BRANCHES));

        $bar->start();

        foreach (self::ISLAMI_BRANCHES as $code => $name) {

            $branch = \Fintech\Banco\Facades\Banco::bankBranch()->list(['location_no' => $code])->first();

            if (! $branch) {
                continue;
            }

            $vendor_code = $branch->vendor_code;

            if ($vendor_code == null) {
                $vendor_code = [];
            }

            if (is_string($vendor_code)) {
                $vendor_code = json_decode($vendor_code, true);
            }

            $vendor_code['remit']['islamibank'] = $name;

            if (\Fintech\Banco\Facades\Banco::bankBranch()->update($branch->getKey(), ['vendor_code' => $vendor_code])) {
                $this->line("Branch ID: {$branch->getKey()} updated successful.");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info('Bank Branch updated successfully.');
    }

    private function addServiceVendor(): void
    {
        $dir = __DIR__.'/../../resources/img/service_vendor/';

        $vendor = [
            'service_vendor_name' => 'Islami Bank',
            'service_vendor_slug' => 'islamibank',
            'service_vendor_data' => [],
            'logo_png' => 'data:image/png;base64,'.base64_encode(file_get_contents("{$dir}/logo_png/islamibank.png")),
            'logo_svg' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents("{$dir}/logo_svg/islamibank.svg")),
            'enabled' => false,
        ];

        if (\Fintech\Business\Facades\Business::serviceVendor()->list(['service_vendor_slug' => $vendor['service_vendor_slug']])->first()) {
            $this->info('Service vendor already exists. Skipping');
        } else {
            \Fintech\Business\Facades\Business::serviceVendor()->create($vendor);
            $this->info('Service vendor created successfully.');
        }
    }

    private function addBeneficiaryAccountTypeCodes(): void
    {
        $bank = \Fintech\Banco\Facades\Banco::bank()
            ->list(['country_id' => 19, 'slug' => 'islami-bank-bangladesh-limited'])
            ->first();

        if (! $bank) {
            return;
        }

        $accounts = [
            [
                'bank_id' => $bank->id,
                'slug' => '01',
                'name' => 'AWCA (Current)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '02',
                'name' => 'MSA (Savings)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '03',
                'name' => 'MSSA (Scheme)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '05',
                'name' => 'MTDRA (Term Deposit)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '06',
                'name' => 'MMSA (Mohr)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '07',
                'name' => 'MHSA (Hajj)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '09',
                'name' => 'SND (Short Notice Deposit)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '10',
                'name' => 'MSA-STAFF',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '11',
                'name' => 'FCA (FC Current)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '12',
                'name' => 'MFCA (FC Savings)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '67',
                'name' => 'SMSA (Student Savings)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '68',
                'name' => 'MNSBA (NRB Savings Bond)',
                'enabled' => true,
            ],
            [
                'bank_id' => $bank->id,
                'slug' => '71',
                'name' => 'Remittance card',
                'enabled' => true,
            ],
        ];

        foreach ($accounts as $entry) {
            \Fintech\Banco\Facades\Banco::beneficiaryAccountType()->create($entry);
        }
    }
}
