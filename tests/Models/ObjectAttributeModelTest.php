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

it('returns empty array when no cards', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cards = $objectAttribute->cards();

    expect($cards)->toBe([]);
});

it('returns cards array when cards relationship exists', function () {
    $objectAttribute = new ObjectAttribute(['id' => '123', 'name' => 'Test Attribute']);

    $cardData = [
        new Card(['id' => '1', 'name' => 'Card 1']),
        new Card(['id' => '2', 'name' => 'Card 2']),
    ];

    $objectAttribute->setRelationships(['cards' => $cardData]);

    $cards = $objectAttribute->cards();

    expect($cards)->toHaveCount(2);
    expect($cards[0])->toBeInstanceOf(Card::class);
    expect($cards[0]->name)->toBe('Card 1');
    expect($cards[1])->toBeInstanceOf(Card::class);
    expect($cards[1]->name)->toBe('Card 2');
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
