<?php

namespace CardTechie\TradingCardApiSdk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CardTechie\TradingCardApiSdk\Commands\TradingCardApiSdkCommand;

class TradingCardApiSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tradingcardapi-sdk')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_tradingcardapi_sdk_table')
            ->hasCommand(TradingCardApiSdkCommand::class);
    }
}
