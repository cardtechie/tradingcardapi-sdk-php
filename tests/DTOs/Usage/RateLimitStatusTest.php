<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\RateLimitStatus;

it('exposes the header trio it was constructed with', function () {
    $status = new RateLimitStatus(1000, 742, 1790000000);

    expect($status->limit)->toBe(1000);
    expect($status->remaining)->toBe(742);
    expect($status->resetAt)->toBe(1790000000);
});

it('reports requests already used', function () {
    $status = new RateLimitStatus(1000, 742, 1790000000);

    expect($status->used())->toBe(258);
});

it('clamps used at zero when remaining exceeds limit', function () {
    // Can happen momentarily as the API rolls a window over.
    $status = new RateLimitStatus(10, 25, 1790000000);

    expect($status->used())->toBe(0);
});

it('reports seconds until reset', function () {
    $status = new RateLimitStatus(100, 50, time() + 120);

    // Allow a second of slack for clock movement during the assertion.
    expect($status->secondsUntilReset())->toBeGreaterThan(115);
    expect($status->secondsUntilReset())->toBeLessThanOrEqual(120);
});

it('clamps seconds until reset at zero for a past timestamp', function () {
    $status = new RateLimitStatus(100, 50, time() - 500);

    expect($status->secondsUntilReset())->toBe(0);
});

it('exposes the reset moment as a DateTimeImmutable', function () {
    $status = new RateLimitStatus(100, 50, 1790000000);

    $dateTime = $status->resetAtDateTime();

    expect($dateTime)->toBeInstanceOf(DateTimeImmutable::class);
    expect($dateTime->getTimestamp())->toBe(1790000000);
});

it('builds from a complete header trio', function () {
    $status = RateLimitStatus::fromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['999'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    expect($status)->toBeInstanceOf(RateLimitStatus::class);
    expect($status->limit)->toBe(1000);
    expect($status->remaining)->toBe(999);
    expect($status->resetAt)->toBe(1790000000);
});

it('builds from flat scalar header values', function () {
    // ErrorResponseParser flattens Guzzle's value lists to strings; the DTO
    // must accept that shape as well as the PSR-7 list shape.
    $status = RateLimitStatus::fromHeaders([
        'X-RateLimit-Limit' => '60',
        'X-RateLimit-Remaining' => '0',
        'X-RateLimit-Reset' => '1790000000',
    ]);

    expect($status->limit)->toBe(60);
    expect($status->remaining)->toBe(0);
});

it('resolves header names case-insensitively', function () {
    // PSR-7 header names are case-insensitive and Guzzle preserves wire casing.
    $status = RateLimitStatus::fromHeaders([
        'x-ratelimit-limit' => ['500'],
        'X-RATELIMIT-REMAINING' => ['250'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    expect($status)->not->toBeNull();
    expect($status->limit)->toBe(500);
    expect($status->remaining)->toBe(250);
});

it('returns null for a partial header trio', function () {
    $status = RateLimitStatus::fromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['999'],
        // No X-RateLimit-Reset — the middleware's precedence guard can omit it.
    ]);

    expect($status)->toBeNull();
});

it('returns null when no rate-limit headers are present', function () {
    expect(RateLimitStatus::fromHeaders(['Content-Type' => ['application/json']]))->toBeNull();
    expect(RateLimitStatus::fromHeaders([]))->toBeNull();
});

it('returns null when a header carries an empty value', function () {
    $status = RateLimitStatus::fromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => [''],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    expect($status)->toBeNull();
});
