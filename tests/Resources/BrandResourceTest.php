<?php

use CardTechie\TradingCardApiSdk\Models\Brand as BrandModel;
use CardTechie\TradingCardApiSdk\Resources\Brand;
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
    cache()->put('tcapi_token', 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->brandResource = new Brand($this->client);
});

it('can be instantiated with client', function () {
    expect($this->brandResource)->toBeInstanceOf(Brand::class);
});

it('can create a brand', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Brand',
                    'description' => 'A test brand',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Brand',
        'description' => 'A test brand',
    ];

    $result = $this->brandResource->create($attributes);

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can create brand without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->brandResource->create();

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can create a brand with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Brand',
                ],
                'relationships' => [
                    'manufacturer' => [
                        'data' => ['type' => 'manufacturers', 'id' => '456'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Test Brand'];
    $relationships = [
        'manufacturer' => [
            'data' => ['type' => 'manufacturers', 'id' => '456'],
        ],
    ];

    $result = $this->brandResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can get a brand by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Brand',
                    'description' => 'A test brand',
                ],
            ],
        ]))
    );

    $result = $this->brandResource->get('123');

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can get a brand by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Brand',
                    'description' => 'A test brand',
                ],
            ],
        ]))
    );

    $params = ['include' => 'sets'];
    $result = $this->brandResource->get('123', $params);

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can get a list of brands', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'brands',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Brand 1',
                    ],
                ],
                [
                    'type' => 'brands',
                    'id' => '124',
                    'attributes' => [
                        'name' => 'Brand 2',
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

    $result = $this->brandResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of brands with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'brands',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Brand 1',
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
    $result = $this->brandResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a brand', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Brand',
                    'description' => 'Updated description',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Brand',
        'description' => 'Updated description',
    ];

    $result = $this->brandResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can update a brand with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'brands',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Brand',
                ],
                'relationships' => [
                    'manufacturer' => [
                        'data' => ['type' => 'manufacturers', 'id' => '789'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Updated Brand'];
    $relationships = [
        'manufacturer' => [
            'data' => ['type' => 'manufacturers', 'id' => '789'],
        ],
    ];

    $result = $this->brandResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(BrandModel::class);
});

it('can delete a brand', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->brandResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});