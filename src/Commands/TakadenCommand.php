<?php

namespace Takaden\Commands;

use Illuminate\Console\Command;

class TakadenCommand extends Command
{
    public $signature = 'takaden';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
