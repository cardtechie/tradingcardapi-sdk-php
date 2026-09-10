<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Stats\EntityCount;

it('can create EntityCount from object with all properties', function () {
    $data = (object) [
        'entity_type' => 'cards',
        'total' => 5000,
        'published' => 4500,
        'draft' => 400,
        'archived' => 100,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount)->toBeInstanceOf(EntityCount::class);
    expect($entityCount->entityType)->toBe('cards');
    expect($entityCount->total)->toBe(5000);
    expect($entityCount->published)->toBe(4500);
    expect($entityCount->draft)->toBe(400);
    expect($entityCount->archived)->toBe(100);
});

it('handles missing properties with defaults', function () {
    $data = (object) [];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount->entityType)->toBe('');
    expect($entityCount->total)->toBe(0);
    expect($entityCount->published)->toBe(0);
    expect($entityCount->draft)->toBe(0);
    expect($entityCount->archived)->toBe(0);
});

it('handles partial properties with defaults', function () {
    $data = (object) [
        'entity_type' => 'sets',
        'total' => 150,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount->entityType)->toBe('sets');
    expect($entityCount->total)->toBe(150);
    expect($entityCount->published)->toBe(0);
    expect($entityCount->draft)->toBe(0);
    expect($entityCount->archived)->toBe(0);
});

it('deserialises the gated payload a read:published token receives', function () {
    // Since cardtechie/tradingcardapi-api#2435 a token without internal,
    // read:all-status or read:draft receives total collapsed onto published and
    // draft zeroed. The keys are still present, so this pins the shape a
    // customer-grade token actually sees.
    $data = (object) [
        'entity_type' => 'set',
        'total' => 120,
        'published' => 120,
        'draft' => 0,
        'archived' => 0,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount->entityType)->toBe('set');
    expect($entityCount->total)->toBe($entityCount->published);
    expect($entityCount->published)->toBe(120);
    expect($entityCount->draft)->toBe(0);
    expect($entityCount->archived)->toBe(0);
});

it('deserialises when the gate drops total and draft entirely', function () {
    // Defensive: the shipped gate preserves the keys, but a future API build
    // that omits them rather than collapsing them must not break the DTO.
    $data = (object) [
        'entity_type' => 'card',
        'published' => 326000,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount->entityType)->toBe('card');
    expect($entityCount->published)->toBe(326000);
    expect($entityCount->total)->toBe(0);
    expect($entityCount->draft)->toBe(0);
});
