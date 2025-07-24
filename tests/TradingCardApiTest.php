<?php

use CardTechie\TradingCardApiSdk\TradingCardApi;
use CardTechie\TradingCardApiSdk\Resources\Card;
use CardTechie\TradingCardApiSdk\Resources\Player;
use CardTechie\TradingCardApiSdk\Resources\Set;
use CardTechie\TradingCardApiSdk\Resources\Team;
use CardTechie\TradingCardApiSdk\Resources\Genre;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use CardTechie\TradingCardApiSdk\Resources\Attribute;
use CardTechie\TradingCardApiSdk\Models\ObjectAttribute;
use GuzzleHttp\Client;

beforeEach(function () {
    config([
        'tradingcardapi.url' => 'https://api.example.com',
        'tradingcardapi.ssl_verify' => true,
        'tradingcardapi.client_id' => 'test-client-id',
        'tradingcardapi.client_secret' => 'test-client-secret',
    ]);
});

it('can be instantiated', function () {
    $api = new TradingCardApi();
    expect($api)->toBeInstanceOf(TradingCardApi::class);
});

it('returns card resource', function () {
    $api = new TradingCardApi();
    $card = $api->card();
    expect($card)->toBeInstanceOf(Card::class);
});

it('returns player resource', function () {
    $api = new TradingCardApi();
    $player = $api->player();
    expect($player)->toBeInstanceOf(Player::class);
});

it('returns set resource', function () {
    $api = new TradingCardApi();
    $set = $api->set();
    expect($set)->toBeInstanceOf(Set::class);
});

it('returns team resource', function () {
    $api = new TradingCardApi();
    $team = $api->team();
    expect($team)->toBeInstanceOf(Team::class);
});

it('returns genre resource', function () {
    $api = new TradingCardApi();
    $genre = $api->genre();
    expect($genre)->toBeInstanceOf(Genre::class);
});

it('returns playerteam resource', function () {
    $api = new TradingCardApi();
    $playerteam = $api->playerteam();
    expect($playerteam)->toBeInstanceOf(Playerteam::class);
});

it('returns attribute resource', function () {
    $api = new TradingCardApi();
    $attribute = $api->attribute();
    expect($attribute)->toBeInstanceOf(Attribute::class);
});

it('returns object attribute resource', function () {
    $api = new TradingCardApi();
    $objectAttribute = $api->objectAttribute();
    expect($objectAttribute)->toBeInstanceOf(ObjectAttribute::class);
});

it('creates guzzle client with correct configuration', function () {
    $api = new TradingCardApi();
    
    $reflection = new ReflectionClass($api);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $client = $clientProperty->getValue($api);
    
    expect($client)->toBeInstanceOf(Client::class);
});