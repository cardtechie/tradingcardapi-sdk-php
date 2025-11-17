<?php

use CardTechie\TradingCardApiSdk\Models\Playerteam as PlayerteamModel;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    // Set up configuration
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    // Pre-populate cache with token to avoid OAuth requests
    cache()->put('tcapi_token_'.md5('test-client-idtest-client-secret'), 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->playerteamResource = new Playerteam($this->client);
});

it('can be instantiated with client', function () {
    expect($this->playerteamResource)->toBeInstanceOf(Playerteam::class);
});

it('can get a list of playerteams', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'playerteams',
                    'id' => '123',
                    'attributes' => [
                        'player_id' => '456',
                        'team_id' => '789',
                    ],
                ],
                [
                    'type' => 'playerteams',
                    'id' => '456',
                    'attributes' => [
                        'player_id' => '111',
                        'team_id' => '222',
                    ],
                ],
            ],
        ]))
    );

    $result = $this->playerteamResource->getList();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
});

it('can get a list of playerteams with params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'playerteams',
                    'id' => '123',
                    'attributes' => [
                        'player_id' => '456',
                        'team_id' => '789',
                    ],
                ],
            ],
        ]))
    );

    $params = ['player_id' => '456', 'team_id' => '789'];
    $result = $this->playerteamResource->getList($params);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(1);
});

it('can create a playerteam', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'playerteams',
                'id' => '123',
                'attributes' => [
                    'player_id' => '456',
                    'team_id' => '789',
                ],
            ],
        ]))
    );

    $attributes = [
        'player_id' => '456',
        'team_id' => '789',
    ];

    $result = $this->playerteamResource->create($attributes);

    expect($result)->toBeInstanceOf(PlayerteamModel::class);
});

it('can create playerteam without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'playerteams',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->playerteamResource->create([]);

    expect($result)->toBeInstanceOf(PlayerteamModel::class);
});
