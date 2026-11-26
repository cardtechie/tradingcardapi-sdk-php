<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\RateLimitStatus;
use CardTechie\TradingCardApiSdk\TradingCardApi;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Facade-level wiring for the passive rate-limit accessor.
 *
 * These are the tests that actually pin the design: `createResource()` builds a
 * FRESH resource on every accessor call, so `$api->usage()` returns a different
 * object each time and per-resource state would be unreadable through the
 * facade. Every assertion below that reads a value back from a *different*
 * resource instance than the one that recorded it would fail if the shared
 * `RateLimitTracker` were replaced by a plain trait property.
 */
beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'validation' => [
            'enabled' => false,
        ],
    ]);

    cache()->put(tokenCacheKey(), 'test-token', 60);

    $this->mockHandler = new MockHandler;

    $this->api = new TradingCardApi;

    // Swap in a mock-backed client. createResource() hands $this->client to
    // each resource at construction time, so resources built after this point
    // all speak to the mock handler.
    $clientProperty = (new ReflectionClass($this->api))->getProperty('client');
    $clientProperty->setAccessible(true);
    $clientProperty->setValue($this->api, new Client([
        'handler' => HandlerStack::create($this->mockHandler),
    ]));

    $this->appendUsageResponse = function (array $headers) {
        $this->mockHandler->append(new GuzzleResponse(200, $headers, json_encode([
            'data' => [
                'type' => 'usage',
                'attributes' => [
                    'limit' => 1000,
                    'remaining' => 742,
                    'resets_at' => '2026-06-27T00:00:00+00:00',
                ],
            ],
        ])));
    };
});

it('reports null before any request has been made', function () {
    expect($this->api->rateLimit())->toBeNull();
});

it('exposes the window captured by a resource call through the facade', function () {
    ($this->appendUsageResponse)([
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '999',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    $this->api->usage()->get();

    $status = $this->api->rateLimit();

    expect($status)->toBeInstanceOf(RateLimitStatus::class);
    expect($status->limit)->toBe(1000);
    expect($status->remaining)->toBe(999);
});

it('lets a freshly built resource read a window recorded by an earlier one', function () {
    // The load-bearing case: the resource that recorded the reading was
    // discarded the moment `$this->api->usage()->get()` returned.
    ($this->appendUsageResponse)([
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '900',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    $this->api->usage()->get();

    expect($this->api->usage()->getRateLimit())->not->toBeNull();
    expect($this->api->usage()->getRateLimit()->remaining)->toBe(900);
});

it('shares one window across resources of different types', function () {
    ($this->appendUsageResponse)([
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '750',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    $this->api->usage()->get();

    expect($this->api->card()->getRateLimit()->remaining)->toBe(750);
    expect($this->api->player()->getRateLimit()->remaining)->toBe(750);
});

it('feeds the same window to the internal client', function () {
    ($this->appendUsageResponse)([
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '600',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    $this->api->usage()->get();

    expect($this->api->internal()->rateLimit()->remaining)->toBe(600);
});

it('keeps two clients on independent windows', function () {
    ($this->appendUsageResponse)([
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '500',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    $this->api->usage()->get();

    expect($this->api->rateLimit())->not->toBeNull();
    expect((new TradingCardApi)->rateLimit())->toBeNull();
});
