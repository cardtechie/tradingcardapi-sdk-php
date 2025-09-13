<?php

use CardTechie\TradingCardApiSdk\Resources\Attribute;
use CardTechie\TradingCardApiSdk\Resources\Brand;
use CardTechie\TradingCardApiSdk\Resources\Card;
use CardTechie\TradingCardApiSdk\Resources\Genre;
use CardTechie\TradingCardApiSdk\Resources\Manufacturer;
use CardTechie\TradingCardApiSdk\Resources\ObjectAttribute;
use CardTechie\TradingCardApiSdk\Resources\Player;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use CardTechie\TradingCardApiSdk\Resources\Set;
use CardTechie\TradingCardApiSdk\Resources\Team;
use CardTechie\TradingCardApiSdk\Resources\Year;
use CardTechie\TradingCardApiSdk\TradingCardApi;
use GuzzleHttp\Client;

beforeEach(function () {
    // Ensure config is available for the package
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);
});

it('can be instantiated', function () {
    $api = new TradingCardApi;
    expect($api)->toBeInstanceOf(TradingCardApi::class);
});

it('returns card resource', function () {
    $api = new TradingCardApi;
    $card = $api->card();
    expect($card)->toBeInstanceOf(Card::class);
});

it('returns player resource', function () {
    $api = new TradingCardApi;
    $player = $api->player();
    expect($player)->toBeInstanceOf(Player::class);
});

it('returns set resource', function () {
    $api = new TradingCardApi;
    $set = $api->set();
    expect($set)->toBeInstanceOf(Set::class);
});

it('returns team resource', function () {
    $api = new TradingCardApi;
    $team = $api->team();
    expect($team)->toBeInstanceOf(Team::class);
});

it('returns genre resource', function () {
    $api = new TradingCardApi;
    $genre = $api->genre();
    expect($genre)->toBeInstanceOf(Genre::class);
});

it('returns playerteam resource', function () {
    $api = new TradingCardApi;
    $playerteam = $api->playerteam();
    expect($playerteam)->toBeInstanceOf(Playerteam::class);
});

it('returns attribute resource', function () {
    $api = new TradingCardApi;
    $attribute = $api->attribute();
    expect($attribute)->toBeInstanceOf(Attribute::class);
});

it('returns brand resource', function () {
    $api = new TradingCardApi;
    $brand = $api->brand();
    expect($brand)->toBeInstanceOf(Brand::class);
});

it('returns manufacturer resource', function () {
    $api = new TradingCardApi;
    $manufacturer = $api->manufacturer();
    expect($manufacturer)->toBeInstanceOf(Manufacturer::class);
});

it('returns year resource', function () {
    $api = new TradingCardApi;
    $year = $api->year();
    expect($year)->toBeInstanceOf(Year::class);
});

it('returns object attribute resource', function () {
    $api = new TradingCardApi;
    $objectAttribute = $api->objectAttribute();
    expect($objectAttribute)->toBeInstanceOf(ObjectAttribute::class);
});

it('creates guzzle client with correct configuration', function () {
    $api = new TradingCardApi;

    $reflection = new ReflectionClass($api);
    $clientProperty = $reflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $client = $clientProperty->getValue($api);

    expect($client)->toBeInstanceOf(Client::class);
});
