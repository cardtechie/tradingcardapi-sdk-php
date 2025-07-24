<?php

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\TradingCardApi;

it('facade returns correct accessor', function () {
    $reflection = new ReflectionClass(TradingCardApiSdk::class);
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);

    $accessor = $method->invoke(new TradingCardApiSdk);

    expect($accessor)->toBe(TradingCardApi::class);
});

it('facade extends Laravel Facade', function () {
    $reflection = new ReflectionClass(TradingCardApiSdk::class);

    expect($reflection->getParentClass()->getName())->toBe(\Illuminate\Support\Facades\Facade::class);
});
