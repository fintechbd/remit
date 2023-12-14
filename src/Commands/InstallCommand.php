<?php

namespace Fintech\Remit\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'remit:install';

    public $description = 'install required fields';

    public function handle(): int
    {

        return self::SUCCESS;
    }
}
