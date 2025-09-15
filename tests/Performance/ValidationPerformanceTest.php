<?php

use CardTechie\TradingCardApiSdk\Services\ResponseValidator;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Clear schema cache before each test
    ResponseValidator::clearSchemaCache();

    Config::set('tradingcardapi.validation', [
        'enabled' => true,
        'strict_mode' => false,
        'log_validation_errors' => false, // Disable logging for performance tests
        'cache_schemas' => true,
    ]);
});

it('validates response within acceptable time limits', function () {
    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Performance Test Card',
                'number' => 'PT1',
                'rarity' => 'Common',
                'year' => 2023,
                'description' => 'A card used for performance testing',
            ],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Test Set',
                ],
            ],
        ],
    ];

    // Measure validation time
    $startTime = microtime(true);

    $result = $validator->validate('card', $cardData, '/v1/cards/123');

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    expect($result)->toBeTrue();
    expect($executionTime)->toBeLessThan(10); // Should complete within 10ms
});

it('benefits from schema caching', function () {
    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Cache Test Card',
                'number' => 'CT1',
            ],
        ],
    ];

    // First validation (schema needs to be loaded)
    $startTime1 = microtime(true);
    $validator->validate('card', $cardData, '/v1/cards/123');
    $endTime1 = microtime(true);
    $firstTime = ($endTime1 - $startTime1) * 1000;

    // Second validation (schema should be cached)
    $startTime2 = microtime(true);
    $validator->validate('card', $cardData, '/v1/cards/456');
    $endTime2 = microtime(true);
    $secondTime = ($endTime2 - $startTime2) * 1000;

    // Second validation should be faster (cached schema), but CI environments are variable
    // In CI, allow for performance variance but ensure caching is at least not significantly slower
    $isCI = getenv('CI') !== false;
    if ($isCI) {
        // In CI, just ensure the second validation isn't dramatically slower (more than 3x)
        expect($secondTime)->toBeLessThan($firstTime * 3);
    } else {
        // In local environments, expect actual caching benefit
        expect($secondTime)->toBeLessThan($firstTime);
    }
});

it('handles large response data efficiently', function () {
    $validator = new ResponseValidator;

    // Create a large collection response
    $largeCollection = [
        'data' => [],
        'meta' => [
            'total' => 100,
        ],
    ];

    // Add 100 card objects
    for ($i = 1; $i <= 100; $i++) {
        $largeCollection['data'][] = [
            'id' => (string) $i,
            'type' => 'cards',
            'attributes' => [
                'name' => "Card {$i}",
                'number' => "C{$i}",
                'rarity' => $i % 2 === 0 ? 'Common' : 'Rare',
                'year' => 2020 + ($i % 4),
            ],
        ];
    }

    $startTime = microtime(true);

    // Use collection rules for this test
    $schema = new \CardTechie\TradingCardApiSdk\Schemas\CardSchema;
    $rules = $schema->getCollectionRules();

    $validator = \Illuminate\Support\Facades\Validator::make($largeCollection, $rules);
    $result = $validator->passes();

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    expect($result)->toBeTrue();
    expect($executionTime)->toBeLessThan(500); // Should complete within 500ms even for large collections
});

it('has minimal performance impact when disabled', function () {
    // Disable validation
    Config::set('tradingcardapi.validation.enabled', false);

    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Disabled Validation Test',
            ],
        ],
    ];

    $startTime = microtime(true);

    $result = $validator->validate('card', $cardData, '/v1/cards/123');

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    expect($result)->toBeTrue();
    expect($executionTime)->toBeLessThan(1); // Should be nearly instant when disabled
});

it('measures memory usage during validation', function () {
    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Memory Test Card',
                'number' => 'MT1',
                'description' => str_repeat('A', 1000), // Large description
            ],
        ],
    ];

    $memoryBefore = memory_get_usage(true);

    $validator->validate('card', $cardData, '/v1/cards/123');

    $memoryAfter = memory_get_usage(true);
    $memoryUsed = $memoryAfter - $memoryBefore;

    // Memory usage should be reasonable (less than 5MB for a single validation)
    // CI environments may have different memory allocation patterns
    $memoryLimit = getenv('CI') ? (5 * 1024 * 1024) : (1024 * 1024);
    expect($memoryUsed)->toBeLessThan($memoryLimit);
});
