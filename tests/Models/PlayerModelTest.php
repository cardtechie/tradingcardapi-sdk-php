<?php

use CardTechie\TradingCardApiSdk\Models\Player;
use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

it('can be instantiated with attributes', function () {
    $player = new Player(['id' => '123', 'first_name' => 'John', 'last_name' => 'Doe']);
    
    expect($player)->toBeInstanceOf(Player::class);
    expect($player->id)->toBe('123');
    expect($player->first_name)->toBe('John');
    expect($player->last_name)->toBe('Doe');
});

it('returns full name attribute', function () {
    $player = new Player(['first_name' => 'John', 'last_name' => 'Doe']);
    
    expect($player->full_name)->toBe('John Doe');
});

it('handles null full name gracefully', function () {
    $player = new Player(['first_name' => null, 'last_name' => 'Doe']);
    
    expect($player->full_name)->toBe(' Doe');
});

it('implements Taxonomy interface', function () {
    $player = new Player();
    
    expect($player)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Models\Taxonomy::class);
});

it('build method returns stdClass object', function () {
    $taxonomy = new \stdClass();
    $data = ['test' => 'data'];
    
    $result = Player::build($taxonomy, $data);
    
    expect($result)->toBeInstanceOf(\stdClass::class);
});

it('getFromApi method exists and is properly defined', function () {
    // Test that the method exists and has proper structure
    expect(method_exists(Player::class, 'getFromApi'))->toBeTrue();
    
    $reflection = new ReflectionMethod(Player::class, 'getFromApi');
    expect($reflection->isStatic())->toBeTrue();
    expect($reflection->isPublic())->toBeTrue();
    
    // Check method parameters
    $parameters = $reflection->getParameters();
    expect($parameters)->toHaveCount(1);
    expect($parameters[0]->getName())->toBe('params');
});