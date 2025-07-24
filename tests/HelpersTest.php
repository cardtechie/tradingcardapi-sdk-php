<?php

use CardTechie\TradingCardApiSdk\TradingCardApi;

// Ensure helper function is loaded
require_once __DIR__ . '/../src/helpers.php';

it('tradingcardapi helper function returns TradingCardApi instance', function () {
    $result = tradingcardapi();
    
    expect($result)->toBeInstanceOf(TradingCardApi::class);
});

it('tradingcardapi helper function returns instance from container', function () {
    $first = tradingcardapi();
    $second = tradingcardapi();
    
    expect($first)->toBeInstanceOf(TradingCardApi::class);
    expect($second)->toBeInstanceOf(TradingCardApi::class);
});