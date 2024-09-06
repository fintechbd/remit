<?php

namespace Fintech\Remit\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Traits\HasCoreSettingTrait;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    use HasCoreSettingTrait;

    public $signature = 'remit:install';

    public $description = 'Configure the system for the `fintech/remit` module';

    private string $module = 'Remit';

    private string $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';

    private string $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

    public function handle(): int
    {
        $this->addDefaultServiceTypes();

        $this->components->twoColumnDetail("[<fg=yellow;options=bold>{$this->module}</>] Installation", '<fg=green;options=bold>COMPLETED</>');

        return self::SUCCESS;
    }

    private function addDefaultServiceTypes(): void
    {
        $this->components->task("[<fg=yellow;options=bold>{$this->module}</>] Creating system default service types", function () {

            $entry = [
                'service_type_name' => 'Money Transfer',
                'service_type_slug' => 'money_transfer',
                'logo_svg' => $this->image_svg.'money_transfer.svg',
                'logo_png' => $this->image_png.'money_transfer.png',
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'service_type_step' => '1',
                'enabled' => true,
            ];

            Business::serviceTypeManager($entry)->execute();
        });
    }

    private function addBankCashPickUpTransfer(): void
    {
        $this->components->task("[<fg=yellow;options=bold>{$this->module}</>] Populating Money Transfer (Bank & Cash Pickup) Service Types", function () {
            $parentId = Business::serviceType()->list(['service_type_slug' => 'fund_deposit'])->first()->id;
            $types = [
                [
                    'service_type_name' => 'Bank Deposit',
                    'service_type_slug' => 'bank_deposit',
                    'logo_svg' => $this->image_svg.'bank_deposit.svg',
                    'logo_png' => $this->image_png.'bank_deposit.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '2',
                    'enabled' => true,
                ],
                [
                    'service_type_parent_id' => Business::serviceType()->list(['service_type_slug' => 'fund_deposit'])->first()->id,
                    'service_type_name' => 'Card Deposit',
                    'service_type_slug' => 'card_deposit',
                    'logo_svg' => $this->image_svg.'card_deposit.svg',
                    'logo_png' => $this->image_png.'card_deposit.png',
                    'service_type_is_parent' => 'yes',
                    'service_type_is_description' => 'no',
                    'service_type_step' => '2',
                    'enabled' => true,
                ],
            ];
            foreach ($types as $entry) {
                Business::serviceTypeManager($entry, $parentId)->execute();
            }
        });
    }
}
