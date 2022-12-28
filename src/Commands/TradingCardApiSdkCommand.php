<?php

namespace CardTechie\TradingCardApiSdk\Commands;

use Illuminate\Console\Command;

class TradingCardApiSdkCommand extends Command
{
    public $signature = 'tradingcardapi-sdk';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
