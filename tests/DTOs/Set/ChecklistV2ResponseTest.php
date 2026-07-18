<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Set\ChecklistV2Response;

it('maps cards, the included set, and pagination from a paginated response', function () {
    $response = (object) [
        'data' => [
            (object) ['type' => 'cards', 'id' => '1', 'attributes' => (object) ['number' => '1']],
            (object) ['type' => 'cards', 'id' => '2', 'attributes' => (object) ['number' => '2']],
        ],
        'included' => [
            (object) ['type' => 'sets', 'id' => '123', 'attributes' => (object) ['name' => 'Test Set']],
        ],
        'meta' => (object) [
            'pagination' => (object) [
                'current_page' => 2,
                'per_page' => 50,
                'total' => 120,
                'total_pages' => 3,
            ],
        ],
    ];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result)->toBeInstanceOf(ChecklistV2Response::class);
    expect($result->cards)->toHaveCount(2);
    expect($result->cards[0]->id)->toBe('1');
    expect($result->included)->toHaveCount(1);
    expect($result->set)->not->toBeNull();
    expect($result->set->id)->toBe('123');
    expect($result->currentPage)->toBe(2);
    expect($result->perPage)->toBe(50);
    expect($result->total)->toBe(120);
    expect($result->totalPages)->toBe(3);
});

it('defaults pagination fields to null when the meta block is absent', function () {
    $response = (object) [
        'data' => [
            (object) ['type' => 'cards', 'id' => '1'],
        ],
        'included' => [
            (object) ['type' => 'sets', 'id' => '123'],
        ],
    ];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result->cards)->toHaveCount(1);
    expect($result->set)->not->toBeNull();
    expect($result->currentPage)->toBeNull();
    expect($result->perPage)->toBeNull();
    expect($result->total)->toBeNull();
    expect($result->totalPages)->toBeNull();
});

it('coerces numeric-string pagination values to ints', function () {
    $response = (object) [
        'data' => [],
        'meta' => (object) [
            'pagination' => (object) [
                'current_page' => '1',
                'per_page' => '25',
                'total' => '10',
                'total_pages' => '1',
            ],
        ],
    ];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result->currentPage)->toBe(1);
    expect($result->perPage)->toBe(25);
    expect($result->total)->toBe(10);
    expect($result->totalPages)->toBe(1);
});

it('extracts the set only from included resources of type sets', function () {
    $response = (object) [
        'data' => [],
        'included' => [
            (object) ['type' => 'oncard', 'id' => 'o1'],
            (object) ['type' => 'sets', 'id' => 'set-1'],
            (object) ['type' => 'attributes', 'id' => 'a1'],
        ],
    ];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result->included)->toHaveCount(3);
    expect($result->set)->not->toBeNull();
    expect($result->set->id)->toBe('set-1');
});

it('returns a null set when included has no set resource', function () {
    $response = (object) [
        'data' => [],
        'included' => [
            (object) ['type' => 'oncard', 'id' => 'o1'],
        ],
    ];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result->set)->toBeNull();
});

it('handles a missing data envelope', function () {
    $response = (object) [];

    $result = ChecklistV2Response::fromResponse($response);

    expect($result->cards)->toBe([]);
    expect($result->included)->toBe([]);
    expect($result->set)->toBeNull();
    expect($result->currentPage)->toBeNull();
    expect($result->perPage)->toBeNull();
    expect($result->total)->toBeNull();
    expect($result->totalPages)->toBeNull();
});
