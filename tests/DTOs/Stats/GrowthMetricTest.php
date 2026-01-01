<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthMetric;

it('can create GrowthMetric from object with all properties', function () {
    $data = (object) [
        'entity_type' => 'cards',
        'current' => 5000,
        'previous' => 4800,
        'change' => 200,
        'percentage_change' => 4.17,
    ];

    $metric = GrowthMetric::fromObject($data);

    expect($metric)->toBeInstanceOf(GrowthMetric::class);
    expect($metric->entityType)->toBe('cards');
    expect($metric->current)->toBe(5000);
    expect($metric->previous)->toBe(4800);
    expect($metric->change)->toBe(200);
    expect($metric->percentageChange)->toBe(4.17);
});

it('handles missing properties with defaults', function () {
    $data = (object) [];

    $metric = GrowthMetric::fromObject($data);

    expect($metric->entityType)->toBe('');
    expect($metric->current)->toBe(0);
    expect($metric->previous)->toBe(0);
    expect($metric->change)->toBe(0);
    expect($metric->percentageChange)->toBe(0.0);
});

it('handles negative growth correctly', function () {
    $data = (object) [
        'entity_type' => 'cards',
        'current' => 4500,
        'previous' => 5000,
        'change' => -500,
        'percentage_change' => -10.0,
    ];

    $metric = GrowthMetric::fromObject($data);

    expect($metric->change)->toBe(-500);
    expect($metric->percentageChange)->toBe(-10.0);
});

it('handles zero change correctly', function () {
    $data = (object) [
        'entity_type' => 'sets',
        'current' => 150,
        'previous' => 150,
        'change' => 0,
        'percentage_change' => 0.0,
    ];

    $metric = GrowthMetric::fromObject($data);

    expect($metric->change)->toBe(0);
    expect($metric->percentageChange)->toBe(0.0);
});
