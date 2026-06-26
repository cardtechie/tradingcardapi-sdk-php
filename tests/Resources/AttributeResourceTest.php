<?php

use CardTechie\TradingCardApiSdk\Models\Attribute as AttributeModel;
use CardTechie\TradingCardApiSdk\Resources\Attribute;
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
    $this->attributeResource = new Attribute($this->client);
});

it('can be instantiated with client', function () {
    expect($this->attributeResource)->toBeInstanceOf(Attribute::class);
});

it('can create an attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                    'value' => 'Test Value',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Attribute',
        'value' => 'Test Value',
    ];

    $result = $this->attributeResource->create($attributes);

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can create attribute without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->attributeResource->create();

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can list attributes with pagination', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'attributes', 'id' => '123', 'attributes' => ['name' => 'Attribute 1']],
                ['type' => 'attributes', 'id' => '456', 'attributes' => ['name' => 'Attribute 2']],
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

    $result = $this->attributeResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(100);
    expect($result->perPage())->toBe(50);
    expect($result->currentPage())->toBe(1);
});

it('falls back gracefully when pagination meta is missing', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'attributes', 'id' => '123', 'attributes' => ['name' => 'Attribute 1']],
                ['type' => 'attributes', 'id' => '456', 'attributes' => ['name' => 'Attribute 2']],
            ],
        ]))
    );

    $result = $this->attributeResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(2);
});

it('can get all attributes as a raw collection', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                ['type' => 'attributes', 'id' => '123', 'attributes' => ['name' => 'Attribute 1']],
                ['type' => 'attributes', 'id' => '456', 'attributes' => ['name' => 'Attribute 2']],
            ],
        ]))
    );

    $result = $this->attributeResource->all();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(2);
});

it('can get an attribute by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Attribute',
                    'value' => 'Test Value',
                ],
            ],
        ]))
    );

    $result = $this->attributeResource->get('123');

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can update an attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Attribute',
                    'value' => 'Updated Value',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Attribute',
        'value' => 'Updated Value',
    ];

    $result = $this->attributeResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can create an attribute with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => ['name' => 'Test Attribute'],
                'relationships' => [
                    'parent' => ['data' => ['type' => 'attributes', 'id' => '456']],
                ],
            ],
        ]))
    );

    $result = $this->attributeResource->create(
        ['name' => 'Test Attribute'],
        ['parent' => ['data' => ['type' => 'attributes', 'id' => '456']]]
    );

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can update an attribute with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'attributes',
                'id' => '123',
                'attributes' => ['name' => 'Updated Attribute'],
            ],
        ]))
    );

    $result = $this->attributeResource->update(
        '123',
        ['name' => 'Updated Attribute'],
        ['parent' => ['data' => ['type' => 'attributes', 'id' => '456']]]
    );

    expect($result)->toBeInstanceOf(AttributeModel::class);
});

it('can delete an attribute', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $result = $this->attributeResource->delete('123');

    expect($result)->toBeNull();
});
