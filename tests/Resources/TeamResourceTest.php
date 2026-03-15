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

it('can create a team with relationships', function () {
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
                'relationships' => [
                    'genre' => [
                        'data' => [
                            'type' => 'genres',
                            'id' => '456',
                        ],
                    ],
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Team',
        'location' => 'Test City',
        'mascot' => 'Test Mascot',
    ];

    $relationships = [
        'genre' => [
            'data' => [
                'type' => 'genres',
                'id' => '456',
            ],
        ],
    ];

    $result = $this->teamResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(TeamModel::class);
});

it('can get a team by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'New York Yankees',
                    'location' => 'New York',
                    'mascot' => 'Yankees',
                ],
            ],
        ]))
    );

    $result = $this->teamResource->get('123');

    expect($result)->toBeInstanceOf(TeamModel::class);
    expect($result->id)->toBe('123');
});

it('can get a team by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'New York Yankees',
                    'location' => 'New York',
                    'mascot' => 'Yankees',
                ],
            ],
        ]))
    );

    $params = ['include' => 'genre'];
    $result = $this->teamResource->get('123', $params);

    expect($result)->toBeInstanceOf(TeamModel::class);
});

it('can list teams with pagination', function () {
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
            'meta' => [
                'pagination' => [
                    'total' => 50,
                    'per_page' => 25,
                    'current_page' => 1,
                    'total_pages' => 2,
                ],
            ],
        ]))
    );

    $result = $this->teamResource->list(['limit' => 25, 'page' => 1]);

    expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    expect($result->total())->toBe(50);
});

it('can update a team', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Team Name',
                    'location' => 'Updated City',
                    'mascot' => 'Updated Mascot',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Team Name',
        'location' => 'Updated City',
        'mascot' => 'Updated Mascot',
    ];

    $result = $this->teamResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(TeamModel::class);
    expect($result->id)->toBe('123');
});

it('can update a team with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Team Name',
                    'location' => 'Updated City',
                    'mascot' => 'Updated Mascot',
                ],
                'relationships' => [
                    'genre' => [
                        'data' => [
                            'type' => 'genres',
                            'id' => '789',
                        ],
                    ],
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Team Name',
        'location' => 'Updated City',
        'mascot' => 'Updated Mascot',
    ];

    $relationships = [
        'genre' => [
            'data' => [
                'type' => 'genres',
                'id' => '789',
            ],
        ],
    ];

    $result = $this->teamResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(TeamModel::class);
});

it('can delete a team', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->teamResource->delete('123');

    // If no exception is thrown, the delete was successful
    expect(true)->toBeTrue();
});

it('can list deleted teams', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'teams',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Deleted Team',
                        'location' => 'Deleted City',
                        'mascot' => 'Deleted Mascot',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 10,
                    'per_page' => 25,
                    'current_page' => 1,
                    'total_pages' => 1,
                ],
            ],
        ]))
    );

    $result = $this->teamResource->listDeleted();

    expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    expect($result->total())->toBe(10);
});

it('can handle empty deleted teams list', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ]))
    );

    $result = $this->teamResource->listDeleted();

    expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    expect($result->total())->toBe(0);
});

it('can get a specific deleted team by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'teams',
                'id' => '123',
                'attributes' => [
                    'name' => 'Deleted Team',
                    'location' => 'Deleted City',
                    'mascot' => 'Deleted Mascot',
                ],
            ],
        ]))
    );

    $result = $this->teamResource->deleted('123');

    expect($result)->toBeInstanceOf(TeamModel::class);
    expect($result->id)->toBe('123');
});
