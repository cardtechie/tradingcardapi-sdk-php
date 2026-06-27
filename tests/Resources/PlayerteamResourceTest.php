<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Models\Playerteam as PlayerteamModel;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

beforeEach(function () {
    // Set up configuration
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    // Pre-populate cache with token to avoid OAuth requests
    cache()->put(tokenCacheKey(), 'test-token', 60);

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

    expect($result)->toBeInstanceOf(Collection::class);
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

    expect($result)->toBeInstanceOf(Collection::class);
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

it('can create a playerteam with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'playerteams',
                'id' => '123',
                'attributes' => ['player_id' => '456', 'team_id' => '789'],
                'relationships' => [
                    'player' => ['data' => ['type' => 'players', 'id' => '456']],
                ],
            ],
        ]))
    );

    $result = $this->playerteamResource->create(
        ['player_id' => '456', 'team_id' => '789'],
        ['player' => ['data' => ['type' => 'players', 'id' => '456']]]
    );

    expect($result)->toBeInstanceOf(PlayerteamModel::class);
});

it('can get all playerteams as a raw collection', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'playerteams', 'id' => '123', 'attributes' => ['player_id' => '456', 'team_id' => '789']],
                ['type' => 'playerteams', 'id' => '456', 'attributes' => ['player_id' => '111', 'team_id' => '222']],
            ],
        ]))
    );

    $result = $this->playerteamResource->all();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(2);
});

it('keeps deprecated getList delegating to all', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'playerteams', 'id' => '123', 'attributes' => ['player_id' => '456', 'team_id' => '789']],
            ],
        ]))
    );

    $result = $this->playerteamResource->getList();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(1);
});

it('can list playerteams with pagination', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'playerteams', 'id' => '123', 'attributes' => ['player_id' => '456', 'team_id' => '789']],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 30,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->playerteamResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(30);
    expect($result->perPage())->toBe(50);
    expect($result->currentPage())->toBe(1);
});

it('can get a playerteam by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'playerteams',
                'id' => '123',
                'attributes' => ['player_id' => '456', 'team_id' => '789'],
            ],
        ]))
    );

    $result = $this->playerteamResource->get('123');

    expect($result)->toBeInstanceOf(PlayerteamModel::class);
    expect($result->id)->toBe('123');
});

it('can update a playerteam', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'playerteams',
                'id' => '123',
                'attributes' => ['player_id' => '456', 'team_id' => '999'],
            ],
        ]))
    );

    $result = $this->playerteamResource->update('123', ['team_id' => '999']);

    expect($result)->toBeInstanceOf(PlayerteamModel::class);
    expect($result->id)->toBe('123');
});

it('can delete a playerteam', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $result = $this->playerteamResource->delete('123');

    expect($result)->toBeNull();
});
