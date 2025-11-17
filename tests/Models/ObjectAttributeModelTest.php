<?php

use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\ObjectAttribute;

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
        'name' => 'Test Attribute',
        'value' => 'Test Value',
        'description' => 'A test object attribute description',
    ];

    $objectAttribute = new ObjectAttribute($attributes);

    expect($objectAttribute->id)->toBe('123');
    expect($objectAttribute->name)->toBe('Test Attribute');
    expect($objectAttribute->value)->toBe('Test Value');
    expect($objectAttribute->description)->toBe('A test object attribute description');
});

it('can be instantiated without attributes', function () {
    $objectAttribute = new ObjectAttribute;

    expect($objectAttribute)->toBeInstanceOf(ObjectAttribute::class);
});

it('returns empty collection when no cards', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cards = $objectAttribute->cards();

    expect($cards)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($cards)->toBeEmpty();
});

it('returns cards collection when cards relationship exists', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cardData = [
        new Card(['id' => '1', 'name' => 'Card 1']),
        new Card(['id' => '2', 'name' => 'Card 2']),
    ];

    $objectAttribute->setRelationships(['cards' => $cardData]);

    $cards = $objectAttribute->cards();

    expect($cards)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($cards)->toHaveCount(2);
    expect($cards->get(0))->toBeInstanceOf(Card::class);
    expect($cards->get(0)->name)->toBe('Card 1');
    expect($cards->get(1))->toBeInstanceOf(Card::class);
    expect($cards->get(1)->name)->toBe('Card 2');
});

it('hasCards returns false when no cards', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    expect($objectAttribute->hasCards())->toBeFalse();
});

it('hasCards returns true when cards exist', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cardData = [
        new Card(['id' => '1', 'name' => 'Card 1']),
    ];

    $objectAttribute->setRelationships(['cards' => $cardData]);

    expect($objectAttribute->hasCards())->toBeTrue();
});

it('cards collection supports collection methods', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cardData = [
        new Card(['id' => '1', 'name' => 'Card 1']),
        new Card(['id' => '2', 'name' => 'Card 2']),
        new Card(['id' => '3', 'name' => 'Card 3']),
    ];

    $objectAttribute->setRelationships(['cards' => $cardData]);

    $cards = $objectAttribute->cards();

    // Test pluck
    $names = $cards->pluck('name');
    expect($names->toArray())->toBe(['Card 1', 'Card 2', 'Card 3']);

    // Test filter
    $filteredCards = $cards->filter(fn ($card) => $card->id === '2');
    expect($filteredCards)->toHaveCount(1);
    expect($filteredCards->first()->name)->toBe('Card 2');

    // Test first
    expect($cards->first()->name)->toBe('Card 1');
});

it('handles null attributes gracefully', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => null]);

    expect($objectAttribute->id)->toBe('123');
    expect($objectAttribute->name)->toBeNull();
});

it('magic get returns null for non-existent attributes', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123']);

    expect($objectAttribute->nonExistentProperty)->toBeNull();
});

it('magic isset works correctly', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    expect(isset($objectAttribute->id))->toBeTrue();
    expect(isset($objectAttribute->name))->toBeTrue();
    expect(isset($objectAttribute->nonExistentProperty))->toBeFalse();
});

it('converts to string correctly', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $jsonString = (string) $objectAttribute;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Attribute');
});

it('converts to string with relationships', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cardData = [
        new Card(['id' => '1', 'name' => 'Card 1']),
    ];

    $objectAttribute->setRelationships(['cards' => $cardData]);

    $jsonString = (string) $objectAttribute;
    $decodedJson = json_decode($jsonString, true);

    expect($decodedJson)->toHaveKey('id', '123');
    expect($decodedJson)->toHaveKey('name', 'Test Attribute');
    expect($decodedJson)->toHaveKey('cards');
    expect($decodedJson['cards'])->toBeArray();
    expect($decodedJson['cards'])->toHaveCount(1);
});
