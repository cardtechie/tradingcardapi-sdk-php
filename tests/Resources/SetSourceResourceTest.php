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
    cache()->put('tcapi_token', 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->setSourceResource = new SetSource($this->client);
});

it('can be instantiated with client', function () {
    expect($this->setSourceResource)->toBeInstanceOf(SetSource::class);
});

it('can create a set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => 'set-456',
                    'source_url' => 'https://example.com/source',
                    'source_name' => 'Example Source',
                    'source_type' => 'checklist',
                ],
            ],
        ]))
    );

    $attributes = [
        'set_id' => 'set-456',
        'source_url' => 'https://example.com/source',
        'source_name' => 'Example Source',
        'source_type' => 'checklist',
    ];

    $result = $this->setSourceResource->create($attributes);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can create set source without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->setSourceResource->create();

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can create a set source with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'source_url' => 'https://example.com/source',
                    'source_type' => 'metadata',
                ],
                'relationships' => [
                    'set' => [
                        'data' => ['type' => 'sets', 'id' => '456'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = [
        'source_url' => 'https://example.com/source',
        'source_type' => 'metadata',
    ];
    $relationships = [
        'set' => [
            'data' => ['type' => 'sets', 'id' => '456'],
        ],
    ];

    $result = $this->setSourceResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can get a set source by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => 'set-456',
                    'source_url' => 'https://example.com/source',
                    'source_name' => 'Example Source',
                    'source_type' => 'images',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->get('123');

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can get a set source by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'set_id' => 'set-456',
                    'source_url' => 'https://example.com/source',
                    'source_type' => 'checklist',
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
                        'source_url' => 'https://example.com/source1',
                        'source_type' => 'checklist',
                    ],
                ],
                [
                    'type' => 'set-sources',
                    'id' => '124',
                    'attributes' => [
                        'source_url' => 'https://example.com/source2',
                        'source_type' => 'metadata',
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

it('can get a list of set sources with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'source_url' => 'https://example.com/source1',
                        'source_type' => 'images',
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
    $result = $this->setSourceResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'source_url' => 'https://example.com/updated-source',
                    'source_name' => 'Updated Source',
                    'source_type' => 'checklist',
                ],
            ],
        ]))
    );

    $attributes = [
        'source_url' => 'https://example.com/updated-source',
        'source_name' => 'Updated Source',
    ];

    $result = $this->setSourceResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can update a set source with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'source_url' => 'https://example.com/updated-source',
                ],
                'relationships' => [
                    'set' => [
                        'data' => ['type' => 'sets', 'id' => '789'],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['source_url' => 'https://example.com/updated-source'];
    $relationships = [
        'set' => [
            'data' => ['type' => 'sets', 'id' => '789'],
        ],
    ];

    $result = $this->setSourceResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can delete a set source', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->setSourceResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});

it('can get set sources for a specific set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'set_id' => 'set-456',
                        'source_url' => 'https://example.com/source1',
                        'source_type' => 'checklist',
                    ],
                ],
                [
                    'type' => 'set-sources',
                    'id' => '124',
                    'attributes' => [
                        'set_id' => 'set-456',
                        'source_url' => 'https://example.com/source2',
                        'source_type' => 'metadata',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 2,
                    'per_page' => 50,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->forSet('set-456');

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->count())->toBe(2);
});

it('can handle list response without meta information', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'source_url' => 'https://example.com/source1',
                        'source_type' => 'checklist',
                    ],
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->count())->toBe(1);
});

it('can update set source without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'set-sources',
                'id' => '123',
                'attributes' => [
                    'source_url' => 'https://example.com/existing-source',
                    'source_type' => 'checklist',
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->update('123');

    expect($result)->toBeInstanceOf(SetSourceModel::class);
});

it('can get set sources for a specific set with additional params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'set-sources',
                    'id' => '123',
                    'attributes' => [
                        'set_id' => 'set-456',
                        'source_url' => 'https://example.com/source1',
                        'source_type' => 'checklist',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 1,
                    'per_page' => 10,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $result = $this->setSourceResource->forSet('set-456', ['limit' => 10]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->count())->toBe(1);
});
