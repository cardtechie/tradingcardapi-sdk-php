<?php

namespace CardTechie\TradingCardApiSdk\Facades;

use CardTechie\TradingCardApiSdk\TradingCardApi;
use Illuminate\Support\Facades\Facade;

/**
 * @see \CardTechie\TradingCardApiSdk\TradingCardApi
 */
class TradingCardApiSdk extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TradingCardApi::class;
    }
}
