<?php

namespace Fintech\Remit\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'remit:install';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
