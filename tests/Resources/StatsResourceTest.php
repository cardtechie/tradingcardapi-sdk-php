<?php

use CardTechie\TradingCardApiSdk\Resources\Stats;
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
    $this->statsResource = new Stats($this->client);
});

it('can be instantiated with client', function () {
    expect($this->statsResource)->toBeInstanceOf(Stats::class);
});

it('can get cards stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'cards',
                    'unit' => 'daily',
                    'count' => 2,
                    'stats' => [
                        [
                            'date' => '2024-01-15',
                            'count' => 50,
                            'total' => 50,
                        ],
                        [
                            'date' => '2024-01-16',
                            'count' => 100,
                            'total' => 150,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('cards');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('cards');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(2);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(2);
    expect($result->stats[0]->date)->toBe('2024-01-15');
    expect($result->stats[0]->count)->toBe(50);
    expect($result->stats[0]->total)->toBe(50);
    expect($result->stats[1]->date)->toBe('2024-01-16');
    expect($result->stats[1]->count)->toBe(100);
    expect($result->stats[1]->total)->toBe(150);
});

it('can get sets stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'sets',
                    'unit' => 'daily',
                    'count' => 1,
                    'stats' => [
                        [
                            'date' => '2024-01-15',
                            'count' => 25,
                            'total' => 25,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('sets');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('sets');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(1);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(1);
    expect($result->stats[0]->date)->toBe('2024-01-15');
    expect($result->stats[0]->count)->toBe(25);
    expect($result->stats[0]->total)->toBe(25);
});

it('can get players stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'players',
                    'unit' => 'daily',
                    'count' => 3,
                    'stats' => [
                        [
                            'date' => '2024-01-10',
                            'count' => 10,
                            'total' => 10,
                        ],
                        [
                            'date' => '2024-01-11',
                            'count' => 15,
                            'total' => 25,
                        ],
                        [
                            'date' => '2024-01-12',
                            'count' => -5,
                            'total' => 20,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('players');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('players');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(3);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(3);
    expect($result->stats[2]->count)->toBe(-5); // Test negative count (deletions)
    expect($result->stats[2]->total)->toBe(20);
});

it('can get teams stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'teams',
                    'unit' => 'daily',
                    'count' => 1,
                    'stats' => [
                        [
                            'date' => '2024-01-20',
                            'count' => 5,
                            'total' => 5,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('teams');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('teams');
    expect($result->count)->toBe(1);
    expect($result->stats)->toHaveCount(1);
});

it('can get brands stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'brands',
                    'unit' => 'daily',
                    'count' => 2,
                    'stats' => [
                        [
                            'date' => '2024-01-18',
                            'count' => 3,
                            'total' => 3,
                        ],
                        [
                            'date' => '2024-01-19',
                            'count' => 7,
                            'total' => 10,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('brands');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('brands');
    expect($result->count)->toBe(2);
    expect($result->stats)->toHaveCount(2);
});

it('handles empty stats response', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'manufacturers',
                    'unit' => 'daily',
                    'count' => 0,
                    'stats' => [],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('manufacturers');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('manufacturers');
    expect($result->count)->toBe(0);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(0);
});
