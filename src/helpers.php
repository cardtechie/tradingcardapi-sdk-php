<?php

use CardTechie\TradingCardApiSdk\TradingCardApi;

if (! function_exists('tradingcardapi')) {
    /**
     * Easy access to the trading card api
     *
     * @return \CardTechie\TradingCardApiSdk\TradingCardApi
     */
    function tradingcardapi()
    {
        return app(TradingCardApi::class);
    }
}
