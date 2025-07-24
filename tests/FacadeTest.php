<?php

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\TradingCardApi;

it('facade returns correct accessor', function () {
    $accessor = TradingCardApiSdk::getFacadeAccessor();
    
    expect($accessor)->toBe(TradingCardApi::class);
});

it('facade extends Laravel Facade', function () {
    expect(TradingCardApiSdk::class)->toExtend(\Illuminate\Support\Facades\Facade::class);
});