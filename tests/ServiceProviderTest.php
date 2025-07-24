<?php

use CardTechie\TradingCardApiSdk\TradingCardApiServiceProvider;
use Spatie\LaravelPackageTools\Package;

it('can configure package correctly', function () {
    $provider = new TradingCardApiServiceProvider(app());
    $package = new Package();
    
    $provider->configurePackage($package);
    
    expect($package->name)->toBe('tradingcardapi');
});

it('extends PackageServiceProvider', function () {
    $provider = new TradingCardApiServiceProvider(app());
    
    expect($provider)->toBeInstanceOf(\Spatie\LaravelPackageTools\PackageServiceProvider::class);
});