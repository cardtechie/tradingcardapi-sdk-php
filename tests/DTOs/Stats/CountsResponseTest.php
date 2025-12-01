<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\CountsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\EntityCount;

it('can create CountsResponse from response object', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'counts' => [
                    (object) [
                        'entity_type' => 'sets',
                        'total' => 150,
                        'published' => 120,
                        'draft' => 20,
                        'archived' => 10,
                    ],
                    (object) [
                        'entity_type' => 'cards',
                        'total' => 5000,
                        'published' => 4500,
                        'draft' => 400,
                        'archived' => 100,
                    ],
                ],
            ],
        ],
    ];

    $countsResponse = CountsResponse::fromResponse($response);

    expect($countsResponse)->toBeInstanceOf(CountsResponse::class);
    expect($countsResponse->counts)->toHaveCount(2);
    expect($countsResponse->counts[0])->toBeInstanceOf(EntityCount::class);
    expect($countsResponse->counts[0]->entityType)->toBe('sets');
    expect($countsResponse->counts[1]->entityType)->toBe('cards');
});

it('handles response with missing counts attribute', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [],
        ],
    ];

    $countsResponse = CountsResponse::fromResponse($response);

    expect($countsResponse->counts)->toHaveCount(0);
});

it('handles response with missing attributes', function () {
    $response = (object) [
        'data' => (object) [],
    ];

    $countsResponse = CountsResponse::fromResponse($response);

    expect($countsResponse->counts)->toHaveCount(0);
});

it('getByEntityType returns null when no match found', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'counts' => [
                    (object) [
                        'entity_type' => 'sets',
                        'total' => 150,
                        'published' => 120,
                        'draft' => 20,
                        'archived' => 10,
                    ],
                ],
            ],
        ],
    ];

    $countsResponse = CountsResponse::fromResponse($response);
    $result = $countsResponse->getByEntityType('players');

    expect($result)->toBeNull();
});

it('getByEntityType returns correct count when match found', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'counts' => [
                    (object) [
                        'entity_type' => 'sets',
                        'total' => 150,
                        'published' => 120,
                        'draft' => 20,
                        'archived' => 10,
                    ],
                    (object) [
                        'entity_type' => 'cards',
                        'total' => 5000,
                        'published' => 4500,
                        'draft' => 400,
                        'archived' => 100,
                    ],
                ],
            ],
        ],
    ];

    $countsResponse = CountsResponse::fromResponse($response);
    $result = $countsResponse->getByEntityType('cards');

    expect($result)->toBeInstanceOf(EntityCount::class);
    expect($result->entityType)->toBe('cards');
    expect($result->total)->toBe(5000);
});
