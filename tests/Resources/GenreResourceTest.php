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
    
    $this->mockHandler = new MockHandler();
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
                        'slug' => 'baseball'
                    ]
                ],
                [
                    'type' => 'genres',
                    'id' => '456',
                    'attributes' => [
                        'name' => 'Football',
                        'slug' => 'football'
                    ]
                ]
            ]
        ]))
    );

    $result = $this->genreResource->list();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result->count())->toBe(2);
});
