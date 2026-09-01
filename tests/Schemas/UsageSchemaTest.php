<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Schemas\UsageSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for the usage response', function () {
    $schema = new UsageSchema;
    $rules = $schema->getRules();

    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');
    expect($rules)->toHaveKey('data.attributes.limit');
    expect($rules)->toHaveKey('data.attributes.remaining');
    expect($rules)->toHaveKey('data.attributes.resets_at');
});

it('does not require data.id', function () {
    // The usage document ships `type` and `attributes` but no `id`. Reusing
    // BaseSchema::getJsonApiRules() would declare `data.id` as required and log
    // a spurious validation warning on every real response, so this pins the
    // rule against a future refactor onto the shared JSON:API rules.
    $schema = new UsageSchema;

    expect($schema->getRules()['data.id'])->toBe('sometimes|string');
});

it('validates a real usage response successfully', function () {
    $schema = new UsageSchema;

    $validData = [
        'data' => [
            'type' => 'usage',
            'attributes' => [
                'limit' => 1000,
                'remaining' => 742,
                'resets_at' => '2026-06-27T00:00:00+00:00',
            ],
        ],
    ];

    $validator = Validator::make($validData, $schema->getRules());

    expect($validator->passes())->toBeTrue();
});

it('rejects a usage response missing its rate-limit attributes', function () {
    $schema = new UsageSchema;

    $invalidData = [
        'data' => [
            'type' => 'usage',
            'attributes' => [],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('data.attributes.limit');
    expect($validator->errors()->keys())->toContain('data.attributes.remaining');
    expect($validator->errors()->keys())->toContain('data.attributes.resets_at');
});
