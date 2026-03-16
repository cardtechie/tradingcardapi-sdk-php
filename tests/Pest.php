<?php

use CardTechie\TradingCardApiSdk\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function tokenCacheKey(string $clientId = 'test-client-id', string $clientSecret = 'test-client-secret', string $scope = ''): string
{
    return \CardTechie\TradingCardApiSdk\Resources\Attribute::buildTokenCacheKey($clientId, $clientSecret, $scope);
}
