<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\DTOs\Workflow\ActionableSetsMeta;

it('parses a full meta block', function () {
    $meta = ActionableSetsMeta::fromObject((object) [
        'total' => 25,
        'full_total' => 137,
        'step' => 'discover_sources',
        'status' => 'pending',
        'priority_filter' => 'high',
    ]);

    expect($meta->total)->toBe(25);
    expect($meta->fullTotal)->toBe(137);
    expect($meta->step)->toBe('discover_sources');
    expect($meta->status)->toBe('pending');
    expect($meta->priorityFilter)->toBe('high');
});

it('tolerates a partial meta block', function () {
    $meta = ActionableSetsMeta::fromObject((object) ['total' => 3]);

    expect($meta->total)->toBe(3);
    expect($meta->fullTotal)->toBeNull();
    expect($meta->step)->toBeNull();
    expect($meta->status)->toBeNull();
    expect($meta->priorityFilter)->toBeNull();
});

it('tolerates an empty meta block', function () {
    $meta = ActionableSetsMeta::fromObject((object) []);

    expect($meta->total)->toBeNull();
    expect($meta->fullTotal)->toBeNull();
});

it('coerces numeric-string counts to integers', function () {
    $meta = ActionableSetsMeta::fromObject((object) ['total' => '4', 'full_total' => '90']);

    expect($meta->total)->toBe(4);
    expect($meta->fullTotal)->toBe(90);
});
