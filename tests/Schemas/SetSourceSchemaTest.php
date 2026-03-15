<?php

use CardTechie\TradingCardApiSdk\Schemas\SetSourceSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single set source response', function () {
    $schema = new SetSourceSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have set source-specific rules
    expect($rules)->toHaveKey('data.attributes.set_id');
    expect($rules)->toHaveKey('data.attributes.source_type');
    expect($rules)->toHaveKey('data.attributes.source_name');
    expect($rules)->toHaveKey('data.attributes.source_url');

    // Should enforce set source type
    expect($rules['data.type'])->toContain('in:set-sources,set_sources,setSources');
    expect($rules['data.attributes.source_type'])->toContain('in:checklist,metadata,images');
});

it('validates valid set source response successfully', function () {
    $schema = new SetSourceSchema;

    $validSetSourceData = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'checklist',
                'source_name' => 'Beckett',
                'source_url' => 'https://www.beckett.com/',
                'verified_at' => '2024-01-15T10:30:00Z',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($validSetSourceData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid set source type', function () {
    $schema = new SetSourceSchema;

    $invalidData = [
        'data' => [
            'id' => '123',
            'type' => 'sets', // Wrong type
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'checklist',
                'source_name' => 'Beckett',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('rejects invalid source_type value', function () {
    $schema = new SetSourceSchema;

    $invalidData = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'invalid', // Invalid value
                'source_name' => 'Beckett',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.source_type'))->toBeTrue();
});

it('validates checklist source_type', function () {
    $schema = new SetSourceSchema;

    $data = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'checklist',
                'source_name' => 'Beckett',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($data, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates metadata source_type', function () {
    $schema = new SetSourceSchema;

    $data = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'metadata',
                'source_name' => 'COMC',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($data, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates images source_type', function () {
    $schema = new SetSourceSchema;

    $data = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'images',
                'source_name' => 'eBay',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($data, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set source with nullable optional fields', function () {
    $schema = new SetSourceSchema;

    $dataWithNulls = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'checklist',
                'source_name' => 'Physical Cards',
                'source_url' => null,
                'verified_at' => null,
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator = Validator::make($dataWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('requires required fields', function () {
    $schema = new SetSourceSchema;

    $missingRequiredFields = [
        'data' => [
            'id' => '123',
            'type' => 'set-sources',
            'attributes' => [
                // Missing required fields
            ],
        ],
    ];

    $validator = Validator::make($missingRequiredFields, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.set_id'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.source_type'))->toBeTrue();
    expect($validator->errors()->has('data.attributes.source_name'))->toBeTrue();
});

it('provides validation rules for collection response', function () {
    $schema = new SetSourceSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have set source-specific rules for collection
    expect($rules)->toHaveKey('data.*.attributes.set_id');
    expect($rules)->toHaveKey('data.*.attributes.source_type');
    expect($rules)->toHaveKey('data.*.attributes.source_name');
});

it('validates valid set source collection response', function () {
    $schema = new SetSourceSchema;

    $validCollectionData = [
        'data' => [
            [
                'id' => '123',
                'type' => 'set-sources',
                'attributes' => [
                    'set_id' => 'set-456',
                    'source_type' => 'checklist',
                    'source_name' => 'Beckett',
                    'source_url' => 'https://www.beckett.com/',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
            [
                'id' => '124',
                'type' => 'set-sources',
                'attributes' => [
                    'set_id' => 'set-457',
                    'source_type' => 'metadata',
                    'source_name' => 'COMC',
                    'source_url' => 'https://www.comc.com/',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ],
    ];

    $validator = Validator::make($validCollectionData, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});

it('accepts alternative type formats', function () {
    $schema = new SetSourceSchema;

    // Test set_sources (snake_case)
    $data1 = [
        'data' => [
            'id' => '123',
            'type' => 'set_sources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'checklist',
                'source_name' => 'Beckett',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator1 = Validator::make($data1, $schema->getRules());
    expect($validator1->passes())->toBeTrue();

    // Test setSources (camelCase)
    $data2 = [
        'data' => [
            'id' => '123',
            'type' => 'setSources',
            'attributes' => [
                'set_id' => 'set-456',
                'source_type' => 'metadata',
                'source_name' => 'COMC',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-15T10:30:00Z',
            ],
        ],
    ];

    $validator2 = Validator::make($data2, $schema->getRules());
    expect($validator2->passes())->toBeTrue();
});
