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

it('returns empty array when no sets', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $sets = $year->sets();

    expect($sets)->toBe([]);
});

it('returns sets array when sets relationship exists', function () {
    $year = new Year(['id' => '123', 'year' => '2023']);

    $setData = [
        new Set(['id' => '1', 'name' => 'Set 1']),
        new Set(['id' => '2', 'name' => 'Set 2']),
    ];

    $year->setRelationships(['sets' => $setData]);

    $sets = $year->sets();

    expect($sets)->toHaveCount(2);
    expect($sets[0])->toBeInstanceOf(Set::class);
    expect($sets[0]->name)->toBe('Set 1');
    expect($sets[1])->toBeInstanceOf(Set::class);
    expect($sets[1]->name)->toBe('Set 2');
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
