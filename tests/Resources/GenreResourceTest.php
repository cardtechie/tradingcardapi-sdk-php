<?php

use CardTechie\TradingCardApiSdk\Resources\Genre;
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
    cache()->put('tcapi_token', 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->genreResource = new Genre($this->client);
});

it('can be instantiated with client', function () {
    expect($this->genreResource)->toBeInstanceOf(Genre::class);
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
        ]))
    );

    $result = $this->genreResource->list();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
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
        ]))
    );

    $result = $this->genreResource->deletedIndex();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
    expect($result[0]->name)->toBe('Basketball');
    expect($result[0]->deleted_at)->toBe('2024-01-15T10:30:00Z');
    expect($result[1]->name)->toBe('Soccer');
    expect($result[1]->deleted_at)->toBe('2024-01-16T14:20:00Z');
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

    expect($result)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Models\Genre::class);
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

    $result = $this->genreResource->deletedIndex();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(0);
});
