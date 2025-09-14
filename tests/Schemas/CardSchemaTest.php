<?php

use CardTechie\TradingCardApiSdk\Schemas\CardSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single card response', function () {
    $schema = new CardSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have card-specific rules
    expect($rules)->toHaveKey('data.attributes.name');
    expect($rules)->toHaveKey('data.attributes.number');
    expect($rules)->toHaveKey('data.attributes.rarity');
    expect($rules)->toHaveKey('data.attributes.year');

    // Should enforce card type
    expect($rules['data.type'])->toContain('in:cards,card');
});

it('validates valid card response successfully', function () {
    $schema = new CardSchema;

    $validCardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Michael Jordan',
                'number' => '23',
                'rarity' => 'Legendary',
                'series' => '1991 Upper Deck',
                'year' => 1991,
                'description' => 'Rookie card of Michael Jordan',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $validator = Validator::make($validCardData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid card type', function () {
    $schema = new CardSchema;

    $invalidCardData = [
        'data' => [
            'id' => '123',
            'type' => 'players', // Wrong type
            'attributes' => [
                'name' => 'Michael Jordan',
            ],
        ],
    ];

    $validator = Validator::make($invalidCardData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('validates card with nullable fields', function () {
    $schema = new CardSchema;

    $cardWithNulls = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => null,
                'rarity' => null,
                'description' => null,
                'year' => null,
            ],
        ],
    ];

    $validator = Validator::make($cardWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates card with included relationships', function () {
    $schema = new CardSchema;

    $cardWithIncludes = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => '1',
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
            [
                'id' => '789',
                'type' => 'players',
                'attributes' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ],
    ];

    $validator = Validator::make($cardWithIncludes, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('provides validation rules for card collection response', function () {
    $schema = new CardSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection-specific rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have card-specific collection rules
    expect($rules)->toHaveKey('data.*.attributes.name');
    expect($rules)->toHaveKey('data.*.attributes.number');
    expect($rules['data.*.type'])->toContain('in:cards,card');
});

it('validates card collection response successfully', function () {
    $schema = new CardSchema;

    $cardCollection = [
        'data' => [
            [
                'id' => '123',
                'type' => 'cards',
                'attributes' => [
                    'name' => 'Card 1',
                    'number' => '1',
                    'rarity' => 'Common',
                ],
            ],
            [
                'id' => '456',
                'type' => 'cards',
                'attributes' => [
                    'name' => 'Card 2',
                    'number' => '2',
                    'rarity' => 'Rare',
                ],
            ],
        ],
        'meta' => [
            'total' => 2,
            'per_page' => 10,
            'current_page' => 1,
        ],
    ];

    $validator = Validator::make($cardCollection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});

it('validates card with year as integer', function () {
    $schema = new CardSchema;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'year' => 2023,
            ],
        ],
    ];

    $validator = Validator::make($cardData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects card with invalid year type', function () {
    $schema = new CardSchema;

    $cardData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'year' => 'twenty-twenty-three', // Should be integer
            ],
        ],
    ];

    $validator = Validator::make($cardData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.attributes.year'))->toBeTrue();
});
