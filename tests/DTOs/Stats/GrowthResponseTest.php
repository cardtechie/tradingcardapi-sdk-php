<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthMetric;
use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthResponse;

it('can create GrowthResponse from response object', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'period' => '7d',
                'metrics' => [
                    (object) [
                        'entity_type' => 'sets',
                        'current' => 150,
                        'previous' => 140,
                        'change' => 10,
                        'percentage_change' => 7.14,
                    ],
                    (object) [
                        'entity_type' => 'cards',
                        'current' => 5000,
                        'previous' => 4800,
                        'change' => 200,
                        'percentage_change' => 4.17,
                    ],
                ],
            ],
        ],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);

    expect($growthResponse)->toBeInstanceOf(GrowthResponse::class);
    expect($growthResponse->period)->toBe('7d');
    expect($growthResponse->metrics)->toHaveCount(2);
    expect($growthResponse->metrics[0])->toBeInstanceOf(GrowthMetric::class);
    expect($growthResponse->metrics[0]->entityType)->toBe('sets');
    expect($growthResponse->metrics[1]->entityType)->toBe('cards');
});

it('handles response with missing metrics array', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'period' => '30d',
            ],
        ],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);

    expect($growthResponse->metrics)->toHaveCount(0);
    expect($growthResponse->period)->toBe('30d');
});

it('handles response with missing period', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'metrics' => [],
            ],
        ],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);

    expect($growthResponse->period)->toBe('');
    expect($growthResponse->metrics)->toHaveCount(0);
});

it('handles response with missing attributes', function () {
    $response = (object) [
        'data' => (object) [],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);

    expect($growthResponse->period)->toBe('');
    expect($growthResponse->metrics)->toHaveCount(0);
});

it('getByEntityType returns null when no match found', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'period' => '7d',
                'metrics' => [
                    (object) [
                        'entity_type' => 'sets',
                        'current' => 150,
                        'previous' => 140,
                        'change' => 10,
                        'percentage_change' => 7.14,
                    ],
                ],
            ],
        ],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);
    $result = $growthResponse->getByEntityType('players');

    expect($result)->toBeNull();
});

it('getByEntityType returns correct metric when match found', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'period' => '7d',
                'metrics' => [
                    (object) [
                        'entity_type' => 'sets',
                        'current' => 150,
                        'previous' => 140,
                        'change' => 10,
                        'percentage_change' => 7.14,
                    ],
                    (object) [
                        'entity_type' => 'cards',
                        'current' => 5000,
                        'previous' => 4800,
                        'change' => 200,
                        'percentage_change' => 4.17,
                    ],
                ],
            ],
        ],
    ];

    $growthResponse = GrowthResponse::fromResponse($response);
    $result = $growthResponse->getByEntityType('cards');

    expect($result)->toBeInstanceOf(GrowthMetric::class);
    expect($result->entityType)->toBe('cards');
    expect($result->change)->toBe(200);
});
