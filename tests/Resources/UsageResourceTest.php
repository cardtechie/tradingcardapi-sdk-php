<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\UsageResponse;
use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Resources\Usage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    // Set up configuration
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    // Pre-populate cache with token to avoid OAuth requests
    cache()->put(tokenCacheKey(), 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->usageResource = new Usage($this->client);
});

it('can be instantiated with client', function () {
    expect($this->usageResource)->toBeInstanceOf(Usage::class);
});

it('can get the current usage window', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'usage',
                'attributes' => [
                    'limit' => 1000,
                    'remaining' => 742,
                    'resets_at' => '2026-06-27T00:00:00+00:00',
                ],
            ],
        ]))
    );

    $result = $this->usageResource->get();

    expect($result)->toBeInstanceOf(UsageResponse::class);
    expect($result->limit)->toBe(1000);
    expect($result->remaining)->toBe(742);
    expect($result->resetsAt)->toBe('2026-06-27T00:00:00+00:00');
    expect($result->used())->toBe(258);
});

it('requests the usage endpoint', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'usage',
                'attributes' => [
                    'limit' => 10,
                    'remaining' => 10,
                    'resets_at' => '2026-06-27T00:00:00+00:00',
                ],
            ],
        ]))
    );

    $this->usageResource->get();

    $request = $this->mockHandler->getLastRequest();
    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())->toBe('/v1/user/usage');
});

it('falls back to an empty resets_at when the attribute is missing', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'usage',
                'attributes' => [
                    'limit' => 500,
                    'remaining' => 100,
                ],
            ],
        ]))
    );

    $result = $this->usageResource->get();

    expect($result->limit)->toBe(500);
    expect($result->remaining)->toBe(100);
    expect($result->resetsAt)->toBe('');
});

it('surfaces a 401 as an AuthenticationException', function () {
    // The pre-cardtechie/tradingcardapi-api#2342 behavior an opaque `tc_` key
    // sees today: the endpoint is gated to interactive portal sessions.
    $this->mockHandler->append(
        new GuzzleResponse(401, [], json_encode([
            'message' => 'Unauthenticated',
        ]))
    );

    expect(fn () => $this->usageResource->get())
        ->toThrow(AuthenticationException::class);
});
