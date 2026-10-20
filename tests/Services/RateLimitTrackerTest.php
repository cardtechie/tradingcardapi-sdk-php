<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\RateLimitStatus;
use CardTechie\TradingCardApiSdk\Services\RateLimitTracker;

it('reports null before anything has been recorded', function () {
    expect((new RateLimitTracker)->get())->toBeNull();
});

it('records a status directly', function () {
    $tracker = new RateLimitTracker;
    $tracker->record(new RateLimitStatus(1000, 900, 1790000000));

    expect($tracker->get()->limit)->toBe(1000);
    expect($tracker->get()->remaining)->toBe(900);
});

it('records from a complete header trio', function () {
    $tracker = new RateLimitTracker;
    $tracker->recordFromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['998'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    expect($tracker->get())->toBeInstanceOf(RateLimitStatus::class);
    expect($tracker->get()->remaining)->toBe(998);
});

it('replaces a prior reading with a newer complete one', function () {
    $tracker = new RateLimitTracker;
    $tracker->recordFromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['998'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);
    $tracker->recordFromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['997'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    expect($tracker->get()->remaining)->toBe(997);
});

it('leaves a prior reading in place when the trio is incomplete', function () {
    // The API's throttle middleware can legitimately omit the headers under its
    // stacked-limiter precedence guard. Such a response says nothing new about
    // the caller's quota, so clearing would discard good state for nothing.
    $tracker = new RateLimitTracker;
    $tracker->recordFromHeaders([
        'X-RateLimit-Limit' => ['1000'],
        'X-RateLimit-Remaining' => ['998'],
        'X-RateLimit-Reset' => ['1790000000'],
    ]);

    $tracker->recordFromHeaders(['Content-Type' => ['application/json']]);

    expect($tracker->get())->not->toBeNull();
    expect($tracker->get()->remaining)->toBe(998);
});

it('stays null when the first recorded response carries no headers', function () {
    $tracker = new RateLimitTracker;
    $tracker->recordFromHeaders(['Content-Type' => ['application/json']]);

    expect($tracker->get())->toBeNull();
});

it('is shared by reference so two holders of the same instance see each other', function () {
    $tracker = new RateLimitTracker;
    $a = $tracker;
    $b = $tracker;

    $a->record(new RateLimitStatus(50, 49, 1790000000));

    expect($b->get()->remaining)->toBe(49);
});
