<?php

use CardTechie\TradingCardApiSdk\TradingCardApi;

it('tradingcardapi helper function returns TradingCardApi instance', function () {
    $result = tradingcardapi();
    
    expect($result)->toBeInstanceOf(TradingCardApi::class);
});

it('tradingcardapi helper function returns same instance from container', function () {
    $first = tradingcardapi();
    $second = tradingcardapi();
    
    expect($first)->toBe($second);
});