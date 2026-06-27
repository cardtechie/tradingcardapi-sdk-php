<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Schemas\SetTodoSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single set-todo response', function () {
    $schema = new SetTodoSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have set-todo-specific rules
    expect($rules)->toHaveKey('data.attributes.status');
    expect($rules)->toHaveKey('data.attributes.step');
    expect($rules)->toHaveKey('data.attributes.set_id');
    expect($rules)->toHaveKey('data.attributes.notes');
    expect($rules)->toHaveKey('data.attributes.created_at');
    expect($rules)->toHaveKey('data.attributes.updated_at');

    // Should enforce set-todo type
    expect($rules['data.type'])->toContain('in:set-todos,set-todo');
});

it('validates valid set-todo response successfully', function () {
    $schema = new SetTodoSchema;

    $validData = [
        'data' => [
            'id' => '42',
            'type' => 'set-todos',
            'attributes' => [
                'status' => 'pending',
                'step' => 'review',
                'set_id' => '123',
                'notes' => 'Needs human review',
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $validator = Validator::make($validData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid set-todo type', function () {
    $schema = new SetTodoSchema;

    $invalidData = [
        'data' => [
            'id' => '42',
            'type' => 'cards', // Wrong type
            'attributes' => [
                'status' => 'pending',
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('accepts the singular set-todo type', function () {
    $schema = new SetTodoSchema;

    $data = [
        'data' => [
            'id' => '42',
            'type' => 'set-todo',
            'attributes' => [
                'status' => 'pending',
            ],
        ],
    ];

    $validator = Validator::make($data, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('validates set-todo with nullable fields', function () {
    $schema = new SetTodoSchema;

    $dataWithNulls = [
        'data' => [
            'id' => '42',
            'type' => 'set-todo',
            'attributes' => [
                'status' => null,
                'step' => null,
                'set_id' => null,
                'notes' => null,
                'created_at' => null,
                'updated_at' => null,
            ],
        ],
    ];

    $validator = Validator::make($dataWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('provides validation rules for set-todo collection response', function () {
    $schema = new SetTodoSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection-specific rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have set-todo-specific collection rules
    expect($rules)->toHaveKey('data.*.attributes.status');
    expect($rules)->toHaveKey('data.*.attributes.step');
    expect($rules['data.*.type'])->toContain('in:set-todos,set-todo');
});

it('validates set-todo collection response successfully', function () {
    $schema = new SetTodoSchema;

    $collection = [
        'data' => [
            [
                'id' => '1',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'pending',
                    'step' => 'identify',
                    'set_id' => '10',
                    'notes' => null,
                    'created_at' => '2026-01-01T00:00:00Z',
                    'updated_at' => '2026-01-01T00:00:00Z',
                ],
            ],
            [
                'id' => '2',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'review',
                    'step' => 'verify',
                    'set_id' => '20',
                    'notes' => 'Flagged',
                    'created_at' => '2026-01-02T00:00:00Z',
                    'updated_at' => '2026-01-02T00:00:00Z',
                ],
            ],
        ],
        'meta' => [
            'total' => 2,
            'per_page' => 50,
            'current_page' => 1,
        ],
    ];

    $validator = Validator::make($collection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});
