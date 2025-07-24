<?php

use CardTechie\TradingCardApiSdk\Models\Player;

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