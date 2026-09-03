<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\RateLimitStatus;
use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Services\RateLimitTracker;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Distinct from ApiRequestErrorHandlingTest's ApiRequestTestClass — Pest loads
 * every test file into the same global namespace, so the names must not clash.
 */
class RateLimitCaptureTestResource
{
    use ApiRequest;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function call(string $url, string $method = 'GET', array $request = [], array $headers = []): object
    {
        return $this->makeRequest($url, $method, $request, $headers);
    }
}

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
    $this->client = new Client(['handler' => HandlerStack::create($this->mockHandler)]);
    $this->resource = new RateLimitCaptureTestResource($this->client);
});

it('captures the rate-limit trio from a successful response', function () {
    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '999',
        'X-RateLimit-Reset' => '1790000000',
    ], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');

    $status = $this->resource->getRateLimit();

    expect($status)->toBeInstanceOf(RateLimitStatus::class);
    expect($status->limit)->toBe(1000);
    expect($status->remaining)->toBe(999);
    expect($status->resetAt)->toBe(1790000000);
});

it('leaves the status null when the response carries no rate-limit headers', function () {
    $this->mockHandler->append(new GuzzleResponse(200, [], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');

    expect($this->resource->getRateLimit())->toBeNull();
});

it('updates the status on each subsequent response', function () {
    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '999',
        'X-RateLimit-Reset' => '1790000000',
    ], json_encode(['data' => []])));
    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '998',
        'X-RateLimit-Reset' => '1790000000',
    ], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');
    expect($this->resource->getRateLimit()->remaining)->toBe(999);

    $this->resource->call('/v1/cards');
    expect($this->resource->getRateLimit()->remaining)->toBe(998);
});

it('keeps the prior status when a later response omits the headers', function () {
    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '900',
        'X-RateLimit-Reset' => '1790000000',
    ], json_encode(['data' => []])));
    $this->mockHandler->append(new GuzzleResponse(200, [], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');
    $this->resource->call('/v1/cards');

    expect($this->resource->getRateLimit()->remaining)->toBe(900);
});

it('still records the window when the request throws a RateLimitException', function () {
    $this->mockHandler->append(new GuzzleResponse(429, [
        'X-RateLimit-Limit' => '60',
        'X-RateLimit-Remaining' => '0',
        'X-RateLimit-Reset' => '1790000000',
        'Retry-After' => '30',
    ], json_encode(['message' => 'Too Many Requests'])));

    expect(fn () => $this->resource->call('/v1/cards'))
        ->toThrow(RateLimitException::class);

    $status = $this->resource->getRateLimit();

    expect($status)->toBeInstanceOf(RateLimitStatus::class);
    expect($status->limit)->toBe(60);
    expect($status->remaining)->toBe(0);
    expect($status->resetAt)->toBe(1790000000);
});

it('does not capture headers from the /oauth/token exchange', function () {
    // Capture lives in makeRequest(), not doRequest(), because retrieveToken()
    // also calls doRequest() — for /oauth/token, whose responses belong to the
    // API's separate oauth.throttle bucket. Force a real token exchange by
    // clearing the cached token, then give that response a rate-limit trio the
    // api bucket must not adopt.
    cache()->forget(tokenCacheKey());

    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '5',
        'X-RateLimit-Remaining' => '4',
        'X-RateLimit-Reset' => '1700000000',
        'X-OAuth-RateLimit-Limit' => '5',
    ], json_encode(['access_token' => 'fresh-token'])));
    $this->mockHandler->append(new GuzzleResponse(200, [], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');

    expect($this->resource->getRateLimit())->toBeNull();
});

it('reports through an injected tracker shared with another resource', function () {
    $tracker = new RateLimitTracker;
    $other = new RateLimitCaptureTestResource($this->client);

    $this->resource->setRateLimitTracker($tracker);
    $other->setRateLimitTracker($tracker);

    $this->mockHandler->append(new GuzzleResponse(200, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '750',
        'X-RateLimit-Reset' => '1790000000',
    ], json_encode(['data' => []])));

    $this->resource->call('/v1/cards');

    expect($other->getRateLimit()->remaining)->toBe(750);
    expect($tracker->get()->remaining)->toBe(750);
});
