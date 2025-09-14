<?php

use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;

it('creates rate limit exception with default values', function () {
    $exception = new RateLimitException;

    expect($exception->getMessage())->toBe('Rate limit exceeded');
    expect($exception->getCode())->toBe(429);
    expect($exception->getHttpStatusCode())->toBe(429);
});

it('stores rate limit information', function () {
    $exception = new RateLimitException(
        'Custom rate limit message',
        429,
        null,
        'rate_limit_exceeded',
        [],
        [],
        1000,    // rate limit
        50,      // remaining
        1234567890, // reset timestamp
        300      // retry after
    );

    expect($exception->getRateLimit())->toBe(1000);
    expect($exception->getRateLimitRemaining())->toBe(50);
    expect($exception->getRateLimitReset())->toBe(1234567890);
    expect($exception->getRetryAfter())->toBe(300);
});

it('converts rate limit reset timestamp to DateTime', function () {
    $resetTimestamp = time() + 3600; // 1 hour from now
    $exception = new RateLimitException(
        context: [],
        rateLimitReset: $resetTimestamp
    );

    $resetDateTime = $exception->getRateLimitResetDateTime();
    expect($resetDateTime)->toBeInstanceOf(DateTime::class);
    expect($resetDateTime->getTimestamp())->toBe($resetTimestamp);
});

it('calculates seconds until rate limit reset', function () {
    $resetTimestamp = time() + 300; // 5 minutes from now
    $exception = new RateLimitException(
        context: [],
        rateLimitReset: $resetTimestamp
    );

    $secondsUntilReset = $exception->getSecondsUntilReset();
    expect($secondsUntilReset)->toBeGreaterThanOrEqual(299);
    expect($secondsUntilReset)->toBeLessThanOrEqual(301);
});

it('uses retry after when reset timestamp is not available', function () {
    $exception = new RateLimitException(
        context: [],
        retryAfter: 120
    );

    expect($exception->getSecondsUntilReset())->toBe(120);
});

it('creates exception from response headers', function () {
    $headers = [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '0',
        'X-RateLimit-Reset' => (string) (time() + 3600),
        'Retry-After' => '300',
    ];

    $exception = RateLimitException::fromHeaders($headers, 'Custom message', ['endpoint' => '/api/test']);

    expect($exception->getMessage())->toBe('Custom message');
    expect($exception->getRateLimit())->toBe(1000);
    expect($exception->getRateLimitRemaining())->toBe(0);
    expect($exception->getRetryAfter())->toBe(300);
    expect($exception->getContext())->toBe(['endpoint' => '/api/test']);
    expect($exception->getApiErrorCode())->toBe('rate_limit_exceeded');
});

it('includes rate limit data in array conversion', function () {
    $exception = new RateLimitException(
        'Rate limit exceeded',
        429,
        null,
        'rate_limit_exceeded',
        [],
        ['endpoint' => '/api/test'],
        1000,
        0,
        time() + 300,
        300
    );

    $array = $exception->toArray();

    expect($array)->toHaveKey('rate_limit', 1000);
    expect($array)->toHaveKey('rate_limit_remaining', 0);
    expect($array)->toHaveKey('rate_limit_reset');
    expect($array)->toHaveKey('retry_after', 300);
    expect($array)->toHaveKey('seconds_until_reset');
});

it('handles missing headers gracefully', function () {
    $exception = RateLimitException::fromHeaders([], 'Rate limited');

    expect($exception->getRateLimit())->toBeNull();
    expect($exception->getRateLimitRemaining())->toBeNull();
    expect($exception->getRateLimitReset())->toBeNull();
    expect($exception->getRetryAfter())->toBeNull();
});
