<?php

use CardTechie\TradingCardApiSdk\Models\SetSource as SetSourceModel;
use CardTechie\TradingCardApiSdk\Resources\SetSource;
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
    $this->setSourceResource = new SetSource($this->client);
});

it('can be instantiated with client', function () {
    expect($this->setSourceResource)->toBeInstanceOf(SetSource::class);
});

it('can get a set source by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'checklist',
                    'source_name' => 'Beckett',
                    'source_url' => 'https://www.beckett.com/...',
                    'verified_at' => '2024-01-15T10:30:00Z',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->get('123');

    expect($result)->toBeInstanceOf(SetSourceModel::class);
    expect($result->id)->toBe('123');
    expect($result->source_type)->toBe('checklist');
    expect($result->source_name)->toBe('Beckett');
});

it('can get a set source with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'metadata',
                    'source_name' => 'COMC',
                    'source_url' => 'https://www.comc.com/',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $params = ['include' => 'set'];
    $result = $this->setSourceResource->get('123', $params);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can get a list of set sources', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'set_id' => '456',
                        'source_type' => 'checklist',
                        'source_name' => 'Beckett',
                        'source_url' => 'https://www.beckett.com/',
                        'created_at' => '2024-01-01T00:00:00Z',
                        'updated_at' => '2024-01-15T10:30:00Z',
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

    $result = $this->setSourceResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'set_id' => '456',
                        'source_type' => 'images',
                        'source_name' => 'eBay',
                        'source_url' => 'https://www.ebay.com/',
                        'created_at' => '2024-01-01T00:00:00Z',
                        'updated_at' => '2024-01-15T10:30:00Z',
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

    $params = ['limit' => 25, 'page' => 2, 'filter' => ['set_id' => '456']];
    $result = $this->setSourceResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can create a set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '789',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'checklist',
                    'source_name' => 'Beckett',
                    'source_url' => 'https://www.beckett.com/',
                    'verified_at' => '2024-01-15T10:30:00Z',
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $attributes = [
        'set_id' => '456',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
        'source_url' => 'https://www.beckett.com/',
        'verified_at' => '2024-01-15T10:30:00Z',
    ];

    $result = $this->setSourceResource->create($attributes);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
    expect($result->id)->toBe('789');
    expect($result->source_type)->toBe('checklist');
    expect($result->source_name)->toBe('Beckett');
});

it('can create set source with minimal attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '789',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'metadata',
                    'source_name' => 'Physical Cards',
                    'source_url' => null,
                    'verified_at' => null,
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $attributes = [
        'set_id' => '456',
        'source_type' => 'metadata',
        'source_name' => 'Physical Cards',
    ];

    $result = $this->setSourceResource->create($attributes);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
    expect($result->source_url)->toBeNull();
    expect($result->verified_at)->toBeNull();
});

it('can update set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'checklist',
                    'source_name' => 'Beckett Updated',
                    'source_url' => 'https://www.beckett.com/updated',
                    'verified_at' => '2024-01-16T10:30:00Z',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-16T10:30:00Z',
                ],
            ],
        ]))
    );

    $attributes = [
        'source_name' => 'Beckett Updated',
        'source_url' => 'https://www.beckett.com/updated',
        'verified_at' => '2024-01-16T10:30:00Z',
    ];

    $result = $this->setSourceResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
    expect($result->source_name)->toBe('Beckett Updated');
});

it('can delete a set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->setSourceResource->delete('123');

    expect(true)->toBeTrue();
});

it('can get list with empty results', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
            'meta' => [
                'pagination' => [
                    'total' => 0,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(0);
});

it('can create checklist source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '789',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'checklist',
                    'source_name' => 'Beckett',
                    'source_url' => 'https://www.beckett.com/',
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->create([
        'set_id' => '456',
        'source_type' => 'checklist',
        'source_name' => 'Beckett',
        'source_url' => 'https://www.beckett.com/',
    ]);

    expect($result->source_type)->toBe('checklist');
});

it('can create metadata source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '789',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'metadata',
                    'source_name' => 'CardboardConnection',
                    'source_url' => 'https://www.cardboardconnection.com/',
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->create([
        'set_id' => '456',
        'source_type' => 'metadata',
        'source_name' => 'CardboardConnection',
        'source_url' => 'https://www.cardboardconnection.com/',
    ]);

    expect($result->source_type)->toBe('metadata');
});

it('can create images source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(201, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '789',
                'attributes' => [
                    'set_id' => '456',
                    'source_type' => 'images',
                    'source_name' => 'eBay',
                    'source_url' => 'https://www.ebay.com/',
                    'created_at' => '2024-01-15T10:30:00Z',
                    'updated_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->create([
        'set_id' => '456',
        'source_type' => 'images',
        'source_name' => 'eBay',
        'source_url' => 'https://www.ebay.com/',
    ]);

    expect($result->source_type)->toBe('images');
});
