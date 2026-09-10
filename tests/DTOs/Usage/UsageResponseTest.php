<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Usage\UsageResponse;

it('can be constructed directly', function () {
    $usage = new UsageResponse(
        limit: 1000,
        remaining: 742,
        resetsAt: '2026-06-27T00:00:00+00:00',
    );

    expect($usage->limit)->toBe(1000);
    expect($usage->remaining)->toBe(742);
    expect($usage->resetsAt)->toBe('2026-06-27T00:00:00+00:00');
});

it('can create UsageResponse from a response object', function () {
    $response = (object) [
        'data' => (object) [
            'type' => 'usage',
            'attributes' => (object) [
                'limit' => 1000,
                'remaining' => 742,
                'resets_at' => '2026-06-27T00:00:00+00:00',
            ],
        ],
    ];

    $usage = UsageResponse::fromResponse($response);

    expect($usage)->toBeInstanceOf(UsageResponse::class);
    expect($usage->limit)->toBe(1000);
    expect($usage->remaining)->toBe(742);
    expect($usage->resetsAt)->toBe('2026-06-27T00:00:00+00:00');
});

it('defaults missing attributes', function () {
    $response = (object) [
        'data' => (object) [
            'type' => 'usage',
            'attributes' => (object) [],
        ],
    ];

    $usage = UsageResponse::fromResponse($response);

    expect($usage->limit)->toBe(0);
    expect($usage->remaining)->toBe(0);
    expect($usage->resetsAt)->toBe('');
});

it('defaults when the document has no data member at all', function () {
    $usage = UsageResponse::fromResponse((object) []);

    expect($usage->limit)->toBe(0);
    expect($usage->remaining)->toBe(0);
    expect($usage->resetsAt)->toBe('');
});

it('derives the used count', function () {
    $usage = new UsageResponse(limit: 1000, remaining: 742, resetsAt: '');

    expect($usage->used())->toBe(258);
});

it('clamps used at zero when remaining exceeds limit', function () {
    $usage = new UsageResponse(limit: 10, remaining: 25, resetsAt: '');

    expect($usage->used())->toBe(0);
});
