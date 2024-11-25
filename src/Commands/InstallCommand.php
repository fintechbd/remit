<?php

namespace Fintech\Remit\Commands;

use Fintech\Business\Facades\Business;
use Fintech\Core\Facades\Core;
use Fintech\Core\Traits\HasCoreSetting;
use Illuminate\Console\Command;
use Throwable;

class InstallCommand extends Command
{
    use HasCoreSetting;

    public $signature = 'remit:install';

    public $description = 'Configure the system for the `fintech/remit` module';

    private string $module = 'Remit';

    private string $image_svg = __DIR__.'/../../resources/img/service_type/logo_svg/';

    private string $image_png = __DIR__.'/../../resources/img/service_type/logo_png/';

    public function handle(): int
    {
        $this->infoMessage('Module Installation', 'RUNNING');

        $this->task('Module Installation', function () {

            $this->addDefaultServiceTypes();

            $this->addSchedulerTasks();

        });

        return self::SUCCESS;
    }

    private function addDefaultServiceTypes(): void
    {
        $this->task('Creating system default service types', function () {

            $entry = [
                'service_type_name' => 'Money Transfer',
                'service_type_slug' => 'money_transfer',
                'logo_svg' => "{$this->image_svg}money_transfer.svg",
                'logo_png' => "{$this->image_png}money_transfer.png",
                'service_type_is_parent' => 'yes',
                'service_type_is_description' => 'no',
                'enabled' => true,
            ];

            Business::serviceTypeManager($entry)->execute();
        });
    }

    /**
     * @throws Throwable
     */
    private function addSchedulerTasks(): void
    {
        $tasks = [
            [
                'name' => 'Update all the remit vendor order status.',
                'description' => 'This scheduled program update all the remittance order in a sequential way as for their vendor progress.',
                'command' => 'remit:order-status-update',
                'enabled' => false,
                'timezone' => 'Asia/Dhaka',
                'interval' => '*/5 * * * *',
                'priority' => 1,
            ],
        ];

        $this->task('Register schedule tasks', function () use (&$tasks) {
            foreach ($tasks as $task) {

                $taskModel = Core::schedule()->findWhere(['command' => $task['command']]);

                if ($taskModel) {
                    continue;
                }

                Core::schedule()->create($task);
            }
        });
    }
}
