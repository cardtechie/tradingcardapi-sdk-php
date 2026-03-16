<?php

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function tokenCacheKey(string $clientId = 'test-client-id', string $clientSecret = 'test-client-secret', string $scope = ''): string
{
    $instance = new class
    {
        use ApiRequest;
    };

    return $instance::buildTokenCacheKey($clientId, $clientSecret, $scope);
}
