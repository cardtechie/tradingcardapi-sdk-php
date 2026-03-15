<?php

use CardTechie\TradingCardApiSdk\Models\Brand;
use CardTechie\TradingCardApiSdk\Models\Set;

beforeEach(function () {
    // Set up configuration
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
    ]);
});

it('can be instantiated with attributes', function () {
    $attributes = [
        'id' => '123',
        'name' => 'Test Brand',
        'description' => 'A test brand description',
    ];

    $brand = new Brand($attributes);

    expect($brand->id)->toBe('123');
    expect($brand->name)->toBe('Test Brand');
    expect($brand->description)->toBe('A test brand description');
});

it('can be instantiated without attributes', function () {
    $brand = new Brand;

    expect($brand)->toBeInstanceOf(Brand::class);
});

it('returns empty collection when no sets', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $sets = $brand->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toBeEmpty();
});

it('returns sets collection when sets relationship exists', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
    ];

    $brand->setRelationships(['sets' => $setData]);

    $sets = $brand->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toHaveCount(2);
    expect($sets->get(0))->toBeInstanceOf(Set::class);
    expect($sets->get(0)->name)->toBe('Set 1');
    expect($sets->get(1))->toBeInstanceOf(Set::class);
    expect($sets->get(1)->name)->toBe('Set 2');
});

it('hasSets returns false when no sets', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    expect($brand->hasSets())->toBeFalse();
});

it('hasSets returns true when sets exist', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $brand->setRelationships(['sets' => $setData]);

    expect($brand->hasSets())->toBeTrue();
});

it('sets collection supports collection methods', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
        new Set(['id' => '3', 'name' => 'Set 3']),
    ];

    $brand->setRelationships(['sets' => $setData]);

    $sets = $brand->sets();

    // Test pluck
    $names = $sets->pluck('name');
    expect($names->toArray())->toBe(['Set 1', 'Set 2', 'Set 3']);

    // Test filter
    $filteredSets = $sets->filter(fn ($set) => $set->id === '2');
    expect($filteredSets)->toHaveCount(1);
    expect($filteredSets->first()->name)->toBe('Set 2');

    // Test first
    expect($sets->first()->name)->toBe('Set 1');
});

it('handles null attributes gracefully', function () {
    $brand = new Brand(['id' => '123', 'name' => null]);

    expect($brand->id)->toBe('123');
    expect($brand->name)->toBeNull();
});

it('magic get returns null for non-existent attributes', function () {
    $brand = new Brand(['id' => '123']);

    expect($brand->nonExistentProperty)->toBeNull();
});

it('magic isset works correctly', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    expect(isset($brand->id))->toBeTrue();
    expect(isset($brand->name))->toBeTrue();
    expect(isset($brand->nonExistentProperty))->toBeFalse();
});

it('converts to string correctly', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $jsonString = (string) $brand;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Brand');
});

it('converts to string with relationships', function () {
    $brand = new Brand(['id' => '123', 'name' => 'Test Brand']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $brand->setRelationships(['sets' => $setData]);

    $jsonString = (string) $brand;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Brand');
    expect($decodedJson)->toHaveKey('sets');
    expect($decodedJson['sets'])->toBeArray();
    expect($decodedJson['sets'])->toHaveCount(1);
});
