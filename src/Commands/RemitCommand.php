<?php

namespace Fintech\Remit\Commands;

use Illuminate\Console\Command;

class RemitCommand extends Command
{
    public $signature = 'remit';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
