<?php

use CardTechie\TradingCardApiSdk\Models\Set;
use CardTechie\TradingCardApiSdk\Models\Year;

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
        'year' => '2023',
        'description' => 'A test year description',
    ];

    $year = new Year($attributes);

    expect($year->id)->toBe('123');
    expect($year->year)->toBe('2023');
    expect($year->description)->toBe('A test year description');
});

it('can be instantiated without attributes', function () {
    $year = new Year;

    expect($year)->toBeInstanceOf(Year::class);
});

it('returns empty collection when no sets', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $sets = $year->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toBeEmpty();
});

it('returns sets collection when sets relationship exists', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
    ];

    $year->setRelationships(['sets' => $setData]);

    $sets = $year->sets();

    expect($sets)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($sets)->toHaveCount(2);
    expect($sets->get(0))->toBeInstanceOf(Set::class);
    expect($sets->get(0)->name)->toBe('Set 1');
    expect($sets->get(1))->toBeInstanceOf(Set::class);
    expect($sets->get(1)->name)->toBe('Set 2');
});

it('hasSets returns false when no sets', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    expect($year->hasSets())->toBeFalse();
});

it('hasSets returns true when sets exist', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $year->setRelationships(['sets' => $setData]);

    expect($year->hasSets())->toBeTrue();
});

it('sets collection supports collection methods', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
        new Set(['id' => '3', 'name' => 'Set 3']),
    ];

    $year->setRelationships(['sets' => $setData]);

    $sets = $year->sets();

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
    $year = new Year(['id' => '123', 'year' => null]);

    expect($year->id)->toBe('123');
    expect($year->year)->toBeNull();
});

it('magic get returns null for non-existent attributes', function () {
    $year = new Year(['id' => '123']);

    expect($year->nonExistentProperty)->toBeNull();
});

it('magic isset works correctly', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    expect(isset($year->id))->toBeTrue();
    expect(isset($year->year))->toBeTrue();
    expect(isset($year->nonExistentProperty))->toBeFalse();
});

it('converts to string correctly', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $jsonString = (string) $year;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('year', '2023');
});

it('converts to string with relationships', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
    ];

    $year->setRelationships(['sets' => $setData]);

    $jsonString = (string) $year;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('year', '2023');
    expect($decodedJson)->toHaveKey('sets');
    expect($decodedJson['sets'])->toBeArray();
    expect($decodedJson['sets'])->toHaveCount(1);
});

// Test parent year functionality
it('returns null parent when no parent relationship exists', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $parent = $year->parent();

    expect($parent)->toBeNull();
});

it('returns parent year when parent relationship exists', function () {
    $year = new Year(['id' => '123', 'year' => '2023', 'parent_year' => 'parent-id']);

    $parentData = [
        new Year(['id' => 'parent-id', 'year' => '2022']),
    ];

    $year->setRelationships(['parent' => $parentData]);

    $parent = $year->parent();

    expect($parent)->toBeInstanceOf(Year::class);
    expect($parent->id)->toBe('parent-id');
    expect($parent->year)->toBe('2022');
});

it('returns empty array when no children', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $children = $year->children();

    expect($children)->toBe([]);
});

it('returns children array when children relationship exists', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $childrenData = [
        new Year(['id' => '1', 'year' => '2023', 'parent_year' => '123']),
        new Year(['id' => '2', 'year' => '2023', 'parent_year' => '123']),
    ];

    $year->setRelationships(['children' => $childrenData]);

    $children = $year->children();

    expect($children)->toHaveCount(2);
    expect($children[0])->toBeInstanceOf(Year::class);
    expect($children[0]->parent_year)->toBe('123');
    expect($children[1])->toBeInstanceOf(Year::class);
    expect($children[1]->parent_year)->toBe('123');
});

it('correctly detects if year has parent', function () {
    $yearWithParent = new Year(['id' => '123', 'year' => '2023', 'parent_year' => 'parent-id']);
    $yearWithoutParent = new Year(['id' => '456', 'year' => '2024']);

    expect($yearWithParent->hasParent())->toBeTrue();
    expect($yearWithoutParent->hasParent())->toBeFalse();
});

it('correctly detects if year has children', function () {
    $yearWithChildren = new Year(['id' => '123', 'year' => '2023']);
    $childrenData = [
        new Year(['id' => '1', 'year' => '2023', 'parent_year' => '123']),
    ];
    $yearWithChildren->setRelationships(['children' => $childrenData]);

    $yearWithoutChildren = new Year(['id' => '456', 'year' => '2024']);

    expect($yearWithChildren->hasChildren())->toBeTrue();
    expect($yearWithoutChildren->hasChildren())->toBeFalse();
});

it('generates correct display name with name field', function () {
    $year = new Year(['id' => '123', 'name' => 'The Year 2023', 'year' => '2023']);

    expect($year->getDisplayName())->toBe('The Year 2023');
});

it('generates correct display name with year field fallback', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    expect($year->getDisplayName())->toBe('2023');
});

it('generates correct display name with description fallback', function () {
    $year = new Year(['id' => '123', 'description' => 'Test Year']);

    expect($year->getDisplayName())->toBe('Test Year');
});

it('generates default display name when no name fields exist', function () {
    $year = new Year(['id' => '123']);

    expect($year->getDisplayName())->toBe('Unknown Year');
});
