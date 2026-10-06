<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Stats\EntityCount;

it('can create EntityCount from object with all properties', function () {
    $data = (object) [
        'entity_type' => 'card',
        'total' => 5000,
        'published' => 4500,
        'draft' => 400,
        'archived' => 100,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount)->toBeInstanceOf(EntityCount::class);
    expect($entityCount->entityType)->toBe('card');
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
        'entity_type' => 'set',
        'total' => 150,
    ];

    $entityCount = EntityCount::fromObject($data);

    expect($entityCount->entityType)->toBe('set');
    expect($entityCount->total)->toBe(150);
    expect($entityCount->published)->toBe(0);
    expect($entityCount->draft)->toBe(0);
    expect($entityCount->archived)->toBe(0);
});
