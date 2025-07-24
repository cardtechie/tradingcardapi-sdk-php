<?php

use CardTechie\TradingCardApiSdk\Models\Card;
use CardTechie\TradingCardApiSdk\Models\Set;

it('can be instantiated with attributes', function () {
    $card = new Card(['id' => '123', 'name' => 'Test Card']);
    
    expect($card)->toBeInstanceOf(Card::class);
    expect($card->id)->toBe('123');
    expect($card->name)->toBe('Test Card');
});

it('formats number attribute correctly with set prefix', function () {
    $card = new Card(['number' => 'PREFIX001']);
    
    $set = new Set(['number_prefix' => 'PREFIX']);
    $card->setRelationships(['set' => [$set]]);
    
    expect($card->number)->toBe('001');
});

it('returns full number when no set prefix', function () {
    $card = new Card(['number' => '001']);
    
    expect($card->number)->toBe('001');
});

it('returns full number attribute', function () {
    $card = new Card(['number' => 'PREFIX001']);
    
    expect($card->full_number)->toBe('PREFIX001');
});

it('returns oncard relationships', function () {
    $card = new Card(['id' => '123']);
    $oncard = ['player1', 'player2'];
    
    $card->setRelationships(['oncard' => $oncard]);
    
    expect($card->oncard())->toBe($oncard);
});

it('returns empty array when no oncard relationships', function () {
    $card = new Card(['id' => '123']);
    
    expect($card->oncard())->toBe([]);
});

it('returns extra attributes', function () {
    $card = new Card(['id' => '123']);
    $attributes = ['special' => 'value'];
    
    $card->setRelationships(['attributes' => $attributes]);
    
    expect($card->extraAttributes())->toBe($attributes);
});

it('returns empty array when no extra attributes', function () {
    $card = new Card(['id' => '123']);
    
    expect($card->extraAttributes())->toBe([]);
});

it('returns set relationship', function () {
    $card = new Card(['id' => '123']);
    $set = new Set(['id' => '456', 'name' => 'Test Set']);
    
    $card->setRelationships(['set' => [$set]]);
    
    expect($card->set())->toBe($set);
});

it('returns null when no set relationship', function () {
    $card = new Card(['id' => '123']);
    
    expect($card->set())->toBeNull();
});

it('handles complex relationships in setRelationships', function () {
    $card = new Card(['id' => '123']);
    
    $playerteam = new \CardTechie\TradingCardApiSdk\Models\Playerteam([
        'id' => 'pt1',
        'player_id' => 'p1',
        'team_id' => 't1'
    ]);
    
    $player = new \CardTechie\TradingCardApiSdk\Models\Player(['id' => 'p1', 'name' => 'Player 1']);
    $team = new \CardTechie\TradingCardApiSdk\Models\Team(['id' => 't1', 'name' => 'Team 1']);
    
    $relationships = [
        'playerteam' => [$playerteam],
        'player' => [$player],
        'team' => [$team]
    ];
    
    $card->setRelationships($relationships);
    
    expect($card->getRelationships())->toHaveKey('playerteam');
    expect($card->getRelationships()['playerteam'][0])->toBe($playerteam);
});