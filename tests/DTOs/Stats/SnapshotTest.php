<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\Snapshot;

it('can create Snapshot from object with all properties', function () {
    $data = (object) [
        'date' => '2024-11-30',
        'entity_type' => 'cards',
        'total' => 5000,
        'published' => 4500,
        'draft' => 400,
        'archived' => 100,
    ];

    $snapshot = Snapshot::fromObject($data);

    expect($snapshot)->toBeInstanceOf(Snapshot::class);
    expect($snapshot->date)->toBe('2024-11-30');
    expect($snapshot->entityType)->toBe('cards');
    expect($snapshot->total)->toBe(5000);
    expect($snapshot->published)->toBe(4500);
    expect($snapshot->draft)->toBe(400);
    expect($snapshot->archived)->toBe(100);
});

it('handles missing properties with defaults', function () {
    $data = (object) [];

    $snapshot = Snapshot::fromObject($data);

    expect($snapshot->date)->toBe('');
    expect($snapshot->entityType)->toBe('');
    expect($snapshot->total)->toBe(0);
    expect($snapshot->published)->toBe(0);
    expect($snapshot->draft)->toBe(0);
    expect($snapshot->archived)->toBe(0);
});

it('handles partial properties with defaults', function () {
    $data = (object) [
        'date' => '2024-11-15',
        'entity_type' => 'sets',
    ];

    $snapshot = Snapshot::fromObject($data);

    expect($snapshot->date)->toBe('2024-11-15');
    expect($snapshot->entityType)->toBe('sets');
    expect($snapshot->total)->toBe(0);
    expect($snapshot->published)->toBe(0);
    expect($snapshot->draft)->toBe(0);
    expect($snapshot->archived)->toBe(0);
});
