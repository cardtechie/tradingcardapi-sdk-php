<?php

use CardTechie\TradingCardApiSdk\Services\ResponseValidator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    // Clear schema cache before each test
    ResponseValidator::clearSchemaCache();

    // Reset configuration to defaults
    Config::set('tradingcardapi.validation', [
        'enabled' => true,
        'strict_mode' => false,
        'log_validation_errors' => true,
        'cache_schemas' => false, // Disable cache for tests
    ]);
});

it('validates valid JSON API response successfully', function () {
    $validator = new ResponseValidator;

    $validData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => 'TC1',
                'rarity' => 'Common',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $result = $validator->validate('card', $validData, '/v1/cards/123');

    expect($result)->toBeTrue();
    expect($validator->isValid())->toBeTrue();
    expect($validator->getErrors())->toBeEmpty();
});

it('detects invalid JSON API response structure', function () {
    $validator = new ResponseValidator;

    $invalidData = [
        'data' => [
            // Missing required 'id' field
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
    ];

    $result = $validator->validate('card', $invalidData, '/v1/cards/123');

    expect($result)->toBeFalse();
    expect($validator->isValid())->toBeFalse();
    expect($validator->getErrors())->not->toBeEmpty();
    expect($validator->getErrors())->toHaveKey('data.id');
});

it('handles missing schema gracefully', function () {
    $validator = new ResponseValidator;

    $data = [
        'data' => [
            'id' => '123',
            'type' => 'nonexistent',
            'attributes' => [],
        ],
    ];

    // Should not fail when schema doesn't exist
    $result = $validator->validate('nonexistent', $data, '/v1/nonexistent/123');

    expect($result)->toBeTrue();
    expect($validator->isValid())->toBeTrue();
});

it('logs validation errors when configured', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with(
            \Mockery::pattern('/API response validation failed for card/'),
            \Mockery::type('array')
        );

    $validator = new ResponseValidator;

    $invalidData = [
        'data' => [
            'type' => 'cards',
            'attributes' => [],
            // Missing required 'id'
        ],
    ];

    $validator->validate('card', $invalidData, '/v1/cards/123');
});

it('throws exception in strict mode', function () {
    Config::set('tradingcardapi.validation.strict_mode', true);

    $validator = new ResponseValidator;

    $invalidData = [
        'data' => [
            'type' => 'cards',
            'attributes' => [],
            // Missing required 'id'
        ],
    ];

    expect(function () use ($validator, $invalidData) {
        $validator->validate('card', $invalidData, '/v1/cards/123');
    })->toThrow(ValidationException::class);
});

it('skips validation when disabled', function () {
    Config::set('tradingcardapi.validation.enabled', false);

    $validator = new ResponseValidator;

    $invalidData = [
        'completely' => 'invalid',
        'structure' => 'here',
    ];

    // Should pass even with invalid data when validation is disabled
    $result = $validator->validate('card', $invalidData, '/v1/cards/123');

    expect($result)->toBeTrue();
});

it('validates card-specific attributes', function () {
    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => 'TC1',
                'rarity' => 'Rare',
                'year' => 2023,
                'description' => 'A test card',
            ],
        ],
    ];

    $result = $validator->validate('card', $cardData, '/v1/cards/123');

    expect($result)->toBeTrue();
    expect($validator->getErrors())->toBeEmpty();
});

it('detects invalid card attribute types', function () {
    $validator = new ResponseValidator;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'year' => 'invalid_year', // Should be integer
            ],
        ],
    ];

    $result = $validator->validate('card', $cardData, '/v1/cards/123');

    expect($result)->toBeFalse();
    expect($validator->getErrors())->toHaveKey('data.attributes.year');
});

it('validates included relationships', function () {
    $validator = new ResponseValidator;

    $dataWithIncludes = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
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

    $result = $validator->validate('card', $dataWithIncludes, '/v1/cards/123');

    expect($result)->toBeTrue();
});

it('detects invalid included relationship structure', function () {
    $validator = new ResponseValidator;

    $dataWithInvalidIncludes = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
        'included' => [
            [
                // Missing required 'id' in included item
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Test Set',
                ],
            ],
        ],
    ];

    $result = $validator->validate('card', $dataWithInvalidIncludes, '/v1/cards/123');

    expect($result)->toBeFalse();
    expect($validator->getErrors())->toHaveKey('included.0.id');
});

it('handles validation exceptions gracefully in lenient mode', function () {
    // Mock a validation error that would cause an exception
    Config::set('tradingcardapi.validation.strict_mode', false);

    $validator = new ResponseValidator;

    // This should not throw an exception even with completely invalid data
    $result = $validator->validate('card', [], '/v1/cards/123');

    expect($result)->toBeFalse();
    expect($validator->getErrors())->not->toBeEmpty();
});

it('resets validation state between calls', function () {
    $validator = new ResponseValidator;

    // First validation (invalid)
    $invalidData = [
        'data' => [
            'type' => 'cards',
            'attributes' => [],
            // Missing 'id'
        ],
    ];

    $validator->validate('card', $invalidData, '/v1/cards/123');
    expect($validator->isValid())->toBeFalse();
    expect($validator->getErrors())->not->toBeEmpty();

    // Second validation (valid)
    $validData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
    ];

    $validator->validate('card', $validData, '/v1/cards/123');
    expect($validator->isValid())->toBeTrue();
    expect($validator->getErrors())->toBeEmpty();
});
