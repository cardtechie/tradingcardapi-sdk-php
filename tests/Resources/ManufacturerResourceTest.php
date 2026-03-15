<?php

use CardTechie\TradingCardApiSdk\Models\Manufacturer as ManufacturerModel;
use CardTechie\TradingCardApiSdk\Resources\Manufacturer;
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
    $this->manufacturerResource = new Manufacturer($this->client);
});

it('can be instantiated with client', function () {
    expect($this->manufacturerResource)->toBeInstanceOf(Manufacturer::class);
});

it('can create a manufacturer', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Manufacturer',
                    'description' => 'A test manufacturer',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Manufacturer',
        'description' => 'A test manufacturer',
    ];

    $result = $this->manufacturerResource->create($attributes);

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can create manufacturer without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->manufacturerResource->create();

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can create a manufacturer with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Manufacturer',
                ],
                'relationships' => [
                    'brand' => [
                        'data' => ['type' => 'brands', 'id' => '456'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Test Manufacturer'];
    $relationships = [
        'brand' => [
            'data' => ['type' => 'brands', 'id' => '456'],
        ],
    ];

    $result = $this->manufacturerResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can get a manufacturer by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Manufacturer',
                    'description' => 'A test manufacturer',
                ],
            ],
        ]))
    );

    $result = $this->manufacturerResource->get('123');

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can get a manufacturer by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Manufacturer',
                    'description' => 'A test manufacturer',
                ],
            ],
        ]))
    );

    $params = ['include' => 'sets'];
    $result = $this->manufacturerResource->get('123', $params);

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can get a list of manufacturers', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'manufacturers',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Manufacturer 1',
                    ],
                ],
                [
                    'type' => 'manufacturers',
                    'id' => '124',
                    'attributes' => [
                        'name' => 'Manufacturer 2',
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

    $result = $this->manufacturerResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of manufacturers with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'manufacturers',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Manufacturer 1',
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
    $result = $this->manufacturerResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a manufacturer', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Manufacturer',
                    'description' => 'Updated description',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Manufacturer',
        'description' => 'Updated description',
    ];

    $result = $this->manufacturerResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can update a manufacturer with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'manufacturers',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Manufacturer',
                ],
                'relationships' => [
                    'brand' => [
                        'data' => ['type' => 'brands', 'id' => '789'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Updated Manufacturer'];
    $relationships = [
        'brand' => [
            'data' => ['type' => 'brands', 'id' => '789'],
        ],
    ];

    $result = $this->manufacturerResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(ManufacturerModel::class);
});

it('can delete a manufacturer', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->manufacturerResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});
