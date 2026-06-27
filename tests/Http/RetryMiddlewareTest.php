<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Http\RetryMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Build a Client whose handler stack has the retry middleware pushed onto a
 * MockHandler, so we can assert exactly how many queued responses get consumed.
 */
function makeRetryClient(MockHandler $mock, array $config = []): Client
{
    $stack = HandlerStack::create($mock);
    $stack->push(RetryMiddleware::make(array_merge([
        'max_attempts' => 3,
        'base_delay' => 1, // 1ms keeps the test fast
    ], $config)));

    return new Client(['handler' => $stack]);
}

it('retries a 429 response then succeeds', function () {
    $mock = new MockHandler([
        new GuzzleResponse(429, [], 'rate limited'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('GET', '/');

    expect($response->getStatusCode())->toBe(200);
    // Both queued responses consumed => exactly one retry occurred.
    expect($mock->count())->toBe(0);
});

it('retries a 503 response then succeeds', function () {
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('GET', '/');

    expect($response->getStatusCode())->toBe(200);
    expect($mock->count())->toBe(0);
});

it('retries a connection error then succeeds', function () {
    $mock = new MockHandler([
        new ConnectException('connection failed', new GuzzleRequest('GET', '/')),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('GET', '/');

    expect($response->getStatusCode())->toBe(200);
    expect($mock->count())->toBe(0);
});

it('does not retry a 4xx that is not 429', function () {
    $mock = new MockHandler([
        new GuzzleResponse(404, [], 'not found'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('GET', '/', ['http_errors' => false]);

    expect($response->getStatusCode())->toBe(404);
    // The second queued response is untouched => no retry on a 404.
    expect($mock->count())->toBe(1);
});

it('stops after max_attempts retries', function () {
    // 1 initial + 2 retries = 3 calls, all 503; max_attempts = 2.
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(503, [], 'unavailable'),
    ]);
    $client = makeRetryClient($mock, ['max_attempts' => 2]);

    $response = $client->request('GET', '/', ['http_errors' => false]);

    expect($response->getStatusCode())->toBe(503);
    // 3 responses queued, 3 consumed (initial + 2 retries) => budget exhausted.
    expect($mock->count())->toBe(0);
});

it('honors a numeric Retry-After header over exponential backoff', function () {
    // base_delay 5000ms would dominate; Retry-After of 0 keeps the test fast
    // while proving the header path is taken (no multi-second wait).
    $mock = new MockHandler([
        new GuzzleResponse(429, ['Retry-After' => '0'], 'rate limited'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock, ['base_delay' => 5000]);

    $start = microtime(true);
    $response = $client->request('GET', '/');
    $elapsedMs = (microtime(true) - $start) * 1000;

    expect($response->getStatusCode())->toBe(200);
    // If exponential backoff had been used the request would have slept ~5s.
    expect($elapsedMs)->toBeLessThan(1000);
});

it('does not retry a non-idempotent POST by default', function () {
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('POST', '/', ['http_errors' => false]);

    expect($response->getStatusCode())->toBe(503);
    // The second queued response is untouched => the POST was not retried.
    expect($mock->count())->toBe(1);
});

it('does not retry a non-idempotent PATCH by default', function () {
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('PATCH', '/', ['http_errors' => false]);

    expect($response->getStatusCode())->toBe(503);
    // The second queued response is untouched => the PATCH was not retried.
    expect($mock->count())->toBe(1);
});

it('still retries an idempotent GET by default', function () {
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    $response = $client->request('GET', '/');

    expect($response->getStatusCode())->toBe(200);
    // Both responses consumed => the GET was retried (idempotent default).
    expect($mock->count())->toBe(0);
});

it('retries a POST when retry_non_idempotent is enabled', function () {
    $mock = new MockHandler([
        new GuzzleResponse(503, [], 'unavailable'),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock, ['retry_non_idempotent' => true]);

    $response = $client->request('POST', '/');

    expect($response->getStatusCode())->toBe(200);
    // Both responses consumed => the POST was retried once the flag is set.
    expect($mock->count())->toBe(0);
});

it('does not retry a connection error on a POST by default', function () {
    $mock = new MockHandler([
        new ConnectException('connection failed', new GuzzleRequest('POST', '/')),
        new GuzzleResponse(200, [], 'ok'),
    ]);
    $client = makeRetryClient($mock);

    // The idempotency gate is applied before the ConnectException branch, so a
    // connection error on a POST must surface rather than retry.
    $threw = false;
    try {
        $client->request('POST', '/', ['http_errors' => false]);
    } catch (ConnectException $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
    // The success response is untouched => no retry occurred on the POST.
    expect($mock->count())->toBe(1);
});

it('returns a callable from make()', function () {
    $middleware = RetryMiddleware::make(['max_attempts' => 3, 'base_delay' => 1000]);

    expect($middleware)->toBeCallable();
});
