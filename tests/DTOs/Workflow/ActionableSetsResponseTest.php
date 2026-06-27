<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Workflow\ActionableSet;
use CardTechie\TradingCardApiSdk\DTOs\Workflow\ActionableSetsResponse;

it('can create ActionableSetsResponse from a JSON:API response object', function () {
    $response = (object) [
        'data' => [
            (object) [
                'id' => '1',
                'type' => 'sets',
                'attributes' => (object) ['name' => '2024 Topps Baseball', 'status' => 'draft'],
            ],
            (object) [
                'id' => '2',
                'type' => 'sets',
                'attributes' => (object) ['name' => '2024 Panini Football', 'status' => 'review'],
            ],
        ],
    ];

    $result = ActionableSetsResponse::fromResponse($response);

    expect($result)->toBeInstanceOf(ActionableSetsResponse::class);
    expect($result->sets)->toHaveCount(2);
    expect($result->sets[0])->toBeInstanceOf(ActionableSet::class);
    expect($result->sets[0]->id)->toBe('1');
    expect($result->sets[0]->type)->toBe('sets');
    expect($result->sets[0]->attributes->name)->toBe('2024 Topps Baseball');
    expect($result->sets[1]->id)->toBe('2');
    expect($result->sets[1]->attributes->status)->toBe('review');
});

it('handles an empty data collection', function () {
    $response = (object) ['data' => []];

    $result = ActionableSetsResponse::fromResponse($response);

    expect($result->sets)->toHaveCount(0);
});

it('handles a missing data key', function () {
    $response = (object) [];

    $result = ActionableSetsResponse::fromResponse($response);

    expect($result->sets)->toHaveCount(0);
});

it('defaults id, type, and attributes when absent on an item', function () {
    $response = (object) [
        'data' => [
            (object) [],
        ],
    ];

    $result = ActionableSetsResponse::fromResponse($response);

    expect($result->sets)->toHaveCount(1);
    expect($result->sets[0]->id)->toBe('');
    expect($result->sets[0]->type)->toBe('sets');
    expect($result->sets[0]->attributes)->toBeObject();
});
