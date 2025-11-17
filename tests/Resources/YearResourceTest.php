<?php

use CardTechie\TradingCardApiSdk\Models\Year as YearModel;
use CardTechie\TradingCardApiSdk\Resources\Year;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Pagination\LengthAwarePaginator;

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
    $this->yearResource = new Year($this->client);
});

it('can be instantiated with client', function () {
    expect($this->yearResource)->toBeInstanceOf(Year::class);
});

it('can create a year', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2023',
                    'description' => 'A test year',
                ],
            ],
        ]))
    );

    $attributes = [
        'year' => '2023',
        'description' => 'A test year',
    ];

    $result = $this->yearResource->create($attributes);

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can create year without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->yearResource->create();

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can create a year with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2023',
                ],
                'relationships' => [
                    'sets' => [
                        'data' => [['type' => 'sets', 'id' => '456']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['year' => '2023'];
    $relationships = [
        'sets' => [
            'data' => [['type' => 'sets', 'id' => '456']],
        ],
    ];

    $result = $this->yearResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can get a year by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2023',
                    'description' => 'A test year',
                ],
            ],
        ]))
    );

    $result = $this->yearResource->get('123');

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can get a year by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2023',
                    'description' => 'A test year',
                ],
            ],
        ]))
    );

    $params = ['include' => 'sets'];
    $result = $this->yearResource->get('123', $params);

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can get a list of years', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'years',
                    'id' => '123',
                    'attributes' => [
                        'year' => '2023',
                    ],
                ],
                [
                    'type' => 'years',
                    'id' => '124',
                    'attributes' => [
                        'year' => '2024',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 100,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->yearResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of years with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'years',
                    'id' => '123',
                    'attributes' => [
                        'year' => '2023',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 100,
                    'per_page' => 25,
                    'current_page' => 2,
                ],
            ],
        ]))
    );

    $params = ['limit' => 25, 'page' => 2];
    $result = $this->yearResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a year', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2024',
                    'description' => 'Updated description',
                ],
            ],
        ]))
    );

    $attributes = [
        'year' => '2024',
        'description' => 'Updated description',
    ];

    $result = $this->yearResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can update a year with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'years',
                'id' => '123',
                'attributes' => [
                    'year' => '2024',
                ],
                'relationships' => [
                    'sets' => [
                        'data' => [['type' => 'sets', 'id' => '789']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['year' => '2024'];
    $relationships = [
        'sets' => [
            'data' => [['type' => 'sets', 'id' => '789']],
        ],
    ];

    $result = $this->yearResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(YearModel::class);
});

it('can delete a year', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->yearResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});
