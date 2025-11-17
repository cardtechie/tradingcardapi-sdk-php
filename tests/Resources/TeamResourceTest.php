<?php

use CardTechie\TradingCardApiSdk\Models\Team as TeamModel;
use CardTechie\TradingCardApiSdk\Resources\Team;
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
    $this->teamResource = new Team($this->client);
});

it('can be instantiated with client', function () {
    expect($this->teamResource)->toBeInstanceOf(Team::class);
});

it('can get a list of teams', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'teams',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'New York Yankees',
                        'location' => 'New York',
                        'mascot' => 'Yankees',
                    ],
                ],
                [
                    'type' => 'teams',
                    'id' => '456',
                    'attributes' => [
                        'name' => 'Boston Red Sox',
                        'location' => 'Boston',
                        'mascot' => 'Red Sox',
                    ],
                ],
            ],
        ]))
    );

    $result = $this->teamResource->getList();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
});

it('can get a list of teams with params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'teams',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'New York Yankees',
                        'location' => 'New York',
                        'mascot' => 'Yankees',
                    ],
                ],
            ],
        ]))
    );

    $params = ['name' => 'Yankees', 'limit' => 10];
    $result = $this->teamResource->getList($params);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(1);
});

it('can create a team', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Team',
                    'location' => 'Test City',
                    'mascot' => 'Test Mascot',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Team',
        'location' => 'Test City',
        'mascot' => 'Test Mascot',
    ];

    $result = $this->teamResource->create($attributes);

    expect($result)->toBeInstanceOf(TeamModel::class);
});

it('can create team without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->teamResource->create([]);

    expect($result)->toBeInstanceOf(TeamModel::class);
});
