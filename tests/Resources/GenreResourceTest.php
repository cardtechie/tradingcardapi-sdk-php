<?php

use CardTechie\TradingCardApiSdk\Models\Genre as GenreModel;
use CardTechie\TradingCardApiSdk\Resources\Genre;
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
    $this->genreResource = new Genre($this->client);
});

it('can be instantiated with client', function () {
    expect($this->genreResource)->toBeInstanceOf(Genre::class);
});

it('can create a genre', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Baseball',
                    'slug' => 'baseball',
                    'description' => 'A baseball genre',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Baseball',
        'description' => 'A baseball genre',
    ];

    $result = $this->genreResource->create($attributes);

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can create genre without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->genreResource->create();

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can create a genre with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Baseball',
                ],
                'relationships' => [
                    'sets' => [
                        'data' => [['type' => 'sets', 'id' => '456']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Baseball'];
    $relationships = [
        'sets' => [
            'data' => [['type' => 'sets', 'id' => '456']],
        ],
    ];

    $result = $this->genreResource->create($attributes, $relationships);

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can get a genre by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Baseball',
                    'slug' => 'baseball',
                    'description' => 'A baseball genre',
                ],
            ],
        ]))
    );

    $result = $this->genreResource->get('123');

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can get a genre by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Baseball',
                    'slug' => 'baseball',
                    'description' => 'A baseball genre',
                ],
            ],
        ]))
    );

    $params = ['include' => 'sets'];
    $result = $this->genreResource->get('123', $params);

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can get a list of genres', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'genres',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Baseball',
                        'slug' => 'baseball',
                    ],
                ],
                [
                    'type' => 'genres',
                    'id' => '456',
                    'attributes' => [
                        'name' => 'Football',
                        'slug' => 'football',
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

    $result = $this->genreResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of genres with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'genres',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Baseball',
                        'slug' => 'baseball',
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
    $result = $this->genreResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a genre', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Baseball',
                    'description' => 'Updated description',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Baseball',
        'description' => 'Updated description',
    ];

    $result = $this->genreResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can update a genre with relationships', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Baseball',
                ],
                'relationships' => [
                    'sets' => [
                        'data' => [['type' => 'sets', 'id' => '789']],
                    ],
                ],
            ],
        ]))
    );

    $attributes = ['name' => 'Updated Baseball'];
    $relationships = [
        'sets' => [
            'data' => [['type' => 'sets', 'id' => '789']],
        ],
    ];

    $result = $this->genreResource->update('123', $attributes, $relationships);

    expect($result)->toBeInstanceOf(GenreModel::class);
});

it('can delete a genre', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->genreResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});

it('can get a list of deleted genres', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'genres',
                    'id' => '789',
                    'attributes' => [
                        'name' => 'Basketball',
                        'slug' => 'basketball',
                        'deleted_at' => '2024-01-15T10:30:00Z',
                    ],
                ],
                [
                    'type' => 'genres',
                    'id' => '101112',
                    'attributes' => [
                        'name' => 'Soccer',
                        'slug' => 'soccer',
                        'deleted_at' => '2024-01-16T14:20:00Z',
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

    $result = $this->genreResource->listDeleted();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a specific deleted genre by id', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'genres',
                'id' => '789',
                'attributes' => [
                    'name' => 'Basketball',
                    'slug' => 'basketball',
                    'description' => 'A deleted basketball genre',
                    'deleted_at' => '2024-01-15T10:30:00Z',
                ],
            ],
        ]))
    );

    $result = $this->genreResource->deleted('789');

    expect($result)->toBeInstanceOf(GenreModel::class);
    expect($result->id)->toBe('789');
    expect($result->name)->toBe('Basketball');
    expect($result->slug)->toBe('basketball');
    expect($result->description)->toBe('A deleted basketball genre');
    expect($result->deleted_at)->toBe('2024-01-15T10:30:00Z');
});

it('can handle empty deleted genres list', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ]))
    );

    $result = $this->genreResource->listDeleted();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
