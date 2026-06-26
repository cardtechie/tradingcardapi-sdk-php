<?php

use CardTechie\TradingCardApiSdk\DTOs\Set\ChecklistResponse;

it('can create ChecklistResponse from a response object', function () {
    $response = (object) [
        'data' => (object) [
            'checklist' => ['card1', 'card2', 'card3'],
            'missing' => ['card4', 'card5'],
            'total_cards' => 5,
        ],
    ];

    $result = ChecklistResponse::fromResponse($response);

    expect($result)->toBeInstanceOf(ChecklistResponse::class);
    expect($result->checklist)->toBe(['card1', 'card2', 'card3']);
    expect($result->missing)->toBe(['card4', 'card5']);
    expect($result->totalCards)->toBe(5);
});

it('defaults totalCards to null when absent', function () {
    $response = (object) [
        'data' => (object) [
            'checklist' => ['card1'],
            'missing' => [],
        ],
    ];

    $result = ChecklistResponse::fromResponse($response);

    expect($result->checklist)->toBe(['card1']);
    expect($result->missing)->toBe([]);
    expect($result->totalCards)->toBeNull();
});

it('handles a missing data envelope', function () {
    $response = (object) [];

    $result = ChecklistResponse::fromResponse($response);

    expect($result->checklist)->toBe([]);
    expect($result->missing)->toBe([]);
    expect($result->totalCards)->toBeNull();
});

it('normalizes an object-shaped checklist payload to an array', function () {
    $response = (object) [
        'data' => (object) [
            'checklist' => (object) ['1' => 'card1', '2' => 'card2'],
            'missing' => (object) [],
        ],
    ];

    $result = ChecklistResponse::fromResponse($response);

    expect($result->checklist)->toBeArray();
    expect($result->checklist)->toBe(['1' => 'card1', '2' => 'card2']);
    expect($result->missing)->toBe([]);
});
