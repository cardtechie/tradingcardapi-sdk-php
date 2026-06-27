<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Stats\StatPoint;
use CardTechie\TradingCardApiSdk\DTOs\Stats\StatsResponse;

it('can create StatsResponse from a response object', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'model' => 'cards',
                'unit' => 'daily',
                'count' => 2,
                'stats' => [
                    (object) ['date' => '2024-01-15', 'count' => 50, 'total' => 50],
                    (object) ['date' => '2024-01-16', 'count' => 100, 'total' => 150],
                ],
            ],
        ],
    ];

    $result = StatsResponse::fromResponse($response);

    expect($result)->toBeInstanceOf(StatsResponse::class);
    expect($result->model)->toBe('cards');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(2);
    expect($result->stats)->toHaveCount(2);
    expect($result->stats[0])->toBeInstanceOf(StatPoint::class);
    expect($result->stats[0]->date)->toBe('2024-01-15');
    expect($result->stats[1]->total)->toBe(150);
});

it('handles an empty stats array', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'model' => 'manufacturers',
                'unit' => 'daily',
                'count' => 0,
                'stats' => [],
            ],
        ],
    ];

    $result = StatsResponse::fromResponse($response);

    expect($result->model)->toBe('manufacturers');
    expect($result->count)->toBe(0);
    expect($result->stats)->toHaveCount(0);
});

it('handles a missing attributes envelope with defaults', function () {
    $response = (object) ['data' => (object) []];

    $result = StatsResponse::fromResponse($response);

    expect($result->model)->toBe('');
    expect($result->unit)->toBe('');
    expect($result->count)->toBe(0);
    expect($result->stats)->toHaveCount(0);
});

it('preserves negative deletion counts in stat points', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'model' => 'players',
                'unit' => 'daily',
                'count' => 1,
                'stats' => [
                    (object) ['date' => '2024-01-12', 'count' => -5, 'total' => 20],
                ],
            ],
        ],
    ];

    $result = StatsResponse::fromResponse($response);

    expect($result->stats[0]->count)->toBe(-5);
    expect($result->stats[0]->total)->toBe(20);
});
