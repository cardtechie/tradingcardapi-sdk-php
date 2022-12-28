<?php

namespace CardTechie\TradingCardApiSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CardTechie\TradingCardApiSdk\TradingCardApiSdk
 */
class TradingCardApiSdk extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CardTechie\TradingCardApiSdk\TradingCardApiSdk::class;
    }
}
