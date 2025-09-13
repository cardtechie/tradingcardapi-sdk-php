<?php

use CardTechie\TradingCardApiSdk\Models\ObjectAttribute as ObjectAttributeModel;
use CardTechie\TradingCardApiSdk\Resources\ObjectAttribute;
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
    $this->objectAttributeResource = new ObjectAttribute($this->client);
});

it('can be instantiated with client', function () {
    expect($this->objectAttributeResource)->toBeInstanceOf(ObjectAttribute::class);
});

it('can create an object attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                    'value' => 'Test Value',
                    'description' => 'A test object attribute',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Attribute',
        'value' => 'Test Value',
        'description' => 'A test object attribute',
    ];

    $result = $this->objectAttributeResource->create($attributes);

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can create object attribute without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->objectAttributeResource->create();

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can create an object attribute with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                ],
                'relationships' => [
                    'cards' => [
                        'data' => [['type' => 'cards', 'id' => '456']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Test Attribute'];
    $relationships = [
        'cards' => [
            'data' => [['type' => 'cards', 'id' => '456']],
        ],
    ];

    $result = $this->objectAttributeResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can get an object attribute by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                    'value' => 'Test Value',
                    'description' => 'A test object attribute',
                ],
            ],
        ]))
    );

    $result = $this->objectAttributeResource->get('123');

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can get an object attribute by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                    'value' => 'Test Value',
                    'description' => 'A test object attribute',
                ],
            ],
        ]))
    );

    $params = ['include' => 'cards'];
    $result = $this->objectAttributeResource->get('123', $params);

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can get a list of object attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'objectAttributes',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Attribute 1',
                    ],
                ],
                [
                    'type' => 'objectAttributes',
                    'id' => '124',
                    'attributes' => [
                        'name' => 'Attribute 2',
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

    $result = $this->objectAttributeResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of object attributes with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'objectAttributes',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Attribute 1',
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
    $result = $this->objectAttributeResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update an object attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Attribute',
                    'value' => 'Updated Value',
                    'description' => 'Updated description',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Attribute',
        'value' => 'Updated Value',
        'description' => 'Updated description',
    ];

    $result = $this->objectAttributeResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can update an object attribute with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'objectAttributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Attribute',
                ],
                'relationships' => [
                    'cards' => [
                        'data' => [['type' => 'cards', 'id' => '789']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Updated Attribute'];
    $relationships = [
        'cards' => [
            'data' => [['type' => 'cards', 'id' => '789']],
        ],
    ];

    $result = $this->objectAttributeResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(ObjectAttributeModel::class);
});

it('can delete an object attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->objectAttributeResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});
