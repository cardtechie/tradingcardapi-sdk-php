<?php

use CardTechie\TradingCardApiSdk\Schemas\SetSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single set response', function () {
    $schema = new SetSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have set-specific rules
    expect($rules)->toHaveKey('data.attributes.name');
    expect($rules)->toHaveKey('data.attributes.card_count');
    expect($rules)->toHaveKey('data.attributes.is_subset');
    expect($rules)->toHaveKey('data.attributes.is_variation');

    // Should enforce set type
    expect($rules['data.type'])->toContain('in:sets,set');
});

it('validates valid set response successfully', function () {
    $schema = new SetSchema;

    $validSetData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => '1991 Upper Deck',
                'description' => 'Classic baseball set',
                'card_count' => 700,
                'year' => 1991,
                'is_subset' => false,
                'is_variation' => false,
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $validator = Validator::make($validSetData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid set type', function () {
    $schema = new SetSchema;

    $invalidSetData = [
        'data' => [
            'id' => '123',
            'type' => 'cards', // Wrong type
            'attributes' => [
                'name' => '1991 Upper Deck',
            ],
        ],
    ];

    $validator = Validator::make($invalidSetData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('validates set with nullable fields', function () {
    $schema = new SetSchema;

    $setWithNulls = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'description' => null,
                'card_count' => null,
                'is_subset' => null,
                'is_variation' => null,
                'year' => null,
            ],
        ],
    ];

    $validator = Validator::make($setWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set with is_variation true', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Chrome Variation',
                'is_variation' => true,
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set with is_variation false', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Base Set',
                'is_variation' => false,
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set with is_subset true', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Insert Subset',
                'is_subset' => true,
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects set with invalid is_variation type', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'is_variation' => 'yes', // Should be boolean
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.is_variation'))->toBeTrue();
});

it('rejects set with invalid is_subset type', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'is_subset' => 'yes', // Should be boolean
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.is_subset'))->toBeTrue();
});

it('validates set with included relationships', function () {
    $schema = new SetSchema;

    $setWithIncludes = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'card_count' => 100,
            ],
        ],
        'included' => [
            [
                'id' => '456',
                'type' => 'brands',
                'attributes' => [
                    'name' => 'Upper Deck',
                ],
            ],
            [
                'id' => '789',
                'type' => 'manufacturers',
                'attributes' => [
                    'name' => 'Topps',
                ],
            ],
        ],
    ];

    $validator = Validator::make($setWithIncludes, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('provides validation rules for set collection response', function () {
    $schema = new SetSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection-specific rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have set-specific collection rules
    expect($rules)->toHaveKey('data.*.attributes.name');
    expect($rules)->toHaveKey('data.*.attributes.card_count');
    expect($rules)->toHaveKey('data.*.attributes.is_subset');
    expect($rules)->toHaveKey('data.*.attributes.is_variation');
    expect($rules['data.*.type'])->toContain('in:sets,set');
});

it('validates set collection response successfully', function () {
    $schema = new SetSchema;

    $setCollection = [
        'data' => [
            [
                'id' => '123',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Set 1',
                    'card_count' => 100,
                    'is_subset' => false,
                    'is_variation' => false,
                ],
            ],
            [
                'id' => '456',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Set 2 Chrome',
                    'card_count' => 100,
                    'is_subset' => false,
                    'is_variation' => true,
                ],
            ],
        ],
        'meta' => [
            'total' => 2,
            'per_page' => 10,
            'current_page' => 1,
        ],
    ];

    $validator = Validator::make($setCollection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set collection with mixed is_variation values', function () {
    $schema = new SetSchema;

    $setCollection = [
        'data' => [
            [
                'id' => '1',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Base Set',
                    'is_variation' => false,
                ],
            ],
            [
                'id' => '2',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Chrome Variation',
                    'is_variation' => true,
                ],
            ],
            [
                'id' => '3',
                'type' => 'sets',
                'attributes' => [
                    'name' => 'Refractor',
                    'is_variation' => null,
                ],
            ],
        ],
    ];

    $validator = Validator::make($setCollection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set with year as integer', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'year' => 2023,
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects set with invalid year type', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'year' => 'twenty-twenty-three', // Should be integer
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.year'))->toBeTrue();
});

it('rejects set with invalid card_count type', function () {
    $schema = new SetSchema;

    $setData = [
        'data' => [
            'id' => '123',
            'type' => 'sets',
            'attributes' => [
                'name' => 'Test Set',
                'card_count' => 'one hundred', // Should be integer
            ],
        ],
    ];

    $validator = Validator::make($setData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.card_count'))->toBeTrue();
});
