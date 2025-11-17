<?php

use CardTechie\TradingCardApiSdk\Models\Manufacturer;
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
        'name' => 'Test Manufacturer',
        'description' => 'A test manufacturer description',
    ];

    $manufacturer = new Manufacturer($attributes);

    expect($manufacturer->id)->toBe('123');
    expect($manufacturer->name)->toBe('Test Manufacturer');
    expect($manufacturer->description)->toBe('A test manufacturer description');
});

it('can be instantiated without attributes', function () {
    $manufacturer = new Manufacturer;

    expect($manufacturer)->toBeInstanceOf(Manufacturer::class);
});

it('returns empty collection when no sets', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $sets = $manufacturer->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toBeEmpty();
});

it('returns sets collection when sets relationship exists', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
    ];

    $manufacturer->setRelationships(['sets' => $setData]);

    $sets = $manufacturer->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toHaveCount(2);
    expect($sets->get(0))->toBeInstanceOf(Set::class);
    expect($sets->get(0)->name)->toBe('Set 1');
    expect($sets->get(1))->toBeInstanceOf(Set::class);
    expect($sets->get(1)->name)->toBe('Set 2');
});

it('hasSets returns false when no sets', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    expect($manufacturer->hasSets())->toBeFalse();
});

it('hasSets returns true when sets exist', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $manufacturer->setRelationships(['sets' => $setData]);

    expect($manufacturer->hasSets())->toBeTrue();
});

it('sets collection supports collection methods', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
        new Set(['id' => '3', 'name' => 'Set 3']),
    ];

    $manufacturer->setRelationships(['sets' => $setData]);

    $sets = $manufacturer->sets();

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
    $manufacturer = new Manufacturer(['id' => '123', 'name' => null]);

    expect($manufacturer->id)->toBe('123');
    expect($manufacturer->name)->toBeNull();
});

it('magic get returns null for non-existent attributes', function () {
    $manufacturer = new Manufacturer(['id' => '123']);

    expect($manufacturer->nonExistentProperty)->toBeNull();
});

it('magic isset works correctly', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    expect(isset($manufacturer->id))->toBeTrue();
    expect(isset($manufacturer->name))->toBeTrue();
    expect(isset($manufacturer->nonExistentProperty))->toBeFalse();
});

it('converts to string correctly', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $jsonString = (string) $manufacturer;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Manufacturer');
});

it('converts to string with relationships', function () {
    $manufacturer = new Manufacturer(['id' => '123', 'name' => 'Test Manufacturer']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $manufacturer->setRelationships(['sets' => $setData]);

    $jsonString = (string) $manufacturer;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Manufacturer');
    expect($decodedJson)->toHaveKey('sets');
    expect($decodedJson['sets'])->toBeArray();
    expect($decodedJson['sets'])->toHaveCount(1);
});
