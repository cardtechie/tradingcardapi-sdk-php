<?php

use CardTechie\TradingCardApiSdk\Schemas\WorkflowSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single workflow response', function () {
    $schema = new WorkflowSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have meta/links rules
    expect($rules)->toHaveKey('meta');
    expect($rules)->toHaveKey('links');
});

it('validates valid workflow response successfully', function () {
    $schema = new WorkflowSchema;

    $validData = [
        'data' => [
            'id' => '7',
            'type' => 'workflow',
            'attributes' => [
                'status' => 'pending',
                'step' => 'identify',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $validator = Validator::make($validData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects workflow response missing required JSON:API fields', function () {
    $schema = new WorkflowSchema;

    $invalidData = [
        'data' => [
            // Missing required 'id'
            'type' => 'workflow',
            'attributes' => [
                'status' => 'pending',
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.id'))->toBeTrue();
});

it('provides validation rules for workflow collection response', function () {
    $schema = new WorkflowSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection-specific rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');
});

it('validates workflow collection response successfully', function () {
    $schema = new WorkflowSchema;

    $collection = [
        'data' => [
            [
                'id' => '1',
                'type' => 'workflow',
                'attributes' => [
                    'status' => 'pending',
                ],
            ],
            [
                'id' => '2',
                'type' => 'workflow',
                'attributes' => [
                    'status' => 'review',
                ],
            ],
        ],
        'meta' => [
            'total' => 2,
        ],
    ];

    $validator = Validator::make($collection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});
