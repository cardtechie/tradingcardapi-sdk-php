<?php

use CardTechie\TradingCardApiSdk\Models\Set as SetModel;
use CardTechie\TradingCardApiSdk\Resources\Set;
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
    $this->setResource = new Set($this->client);
});

it('can be instantiated with client', function () {
    expect($this->setResource)->toBeInstanceOf(Set::class);
});

it('can create a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'sets',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Set',
                    'year' => '2023',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Test Set',
        'year' => '2023',
    ];

    $result = $this->setResource->create($attributes);

    expect($result)->toBeInstanceOf(SetModel::class);
});

it('can get a set by id with default includes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'sets',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Set',
                    'year' => '2023',
                ],
            ],
        ]))
    );

    $result = $this->setResource->get('123');

    expect($result)->toBeInstanceOf(SetModel::class);
});

it('can get a set by id with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'sets',
                'id' => '123',
                'attributes' => [
                    'name' => 'Test Set',
                    'year' => '2023',
                ],
            ],
        ]))
    );

    $params = ['include' => 'genre'];
    $result = $this->setResource->get('123', $params);

    expect($result)->toBeInstanceOf(SetModel::class);
});

it('can get a list of sets', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'sets',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Set 1',
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

    $result = $this->setResource->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get a list of sets with custom params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'sets',
                    'id' => '123',
                    'attributes' => [
                        'name' => 'Set 1',
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
    $result = $this->setResource->list($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can update a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'sets',
                'id' => '123',
                'attributes' => [
                    'name' => 'Updated Set',
                    'year' => '2024',
                ],
            ],
        ]))
    );

    $attributes = [
        'name' => 'Updated Set',
        'year' => '2024',
    ];

    $result = $this->setResource->update('123', $attributes);

    expect($result)->toBeInstanceOf(SetModel::class);
});

it('can get checklist for a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'checklist' => ['card1', 'card2', 'card3'],
                'missing' => ['card4', 'card5'],
            ],
        ]))
    );

    $result = $this->setResource->checklist('123');

    expect($result)->toBeObject();
});

it('can add missing cards to a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'success' => true,
            'message' => 'Missing cards added',
        ]))
    );

    $result = $this->setResource->addMissingCards('123');

    expect($result)->toBeObject();
});

it('can add checklist to a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'success' => true,
            'message' => 'Checklist added',
        ]))
    );

    $request = [
        'json' => [
            'checklist' => ['card1', 'card2'],
        ],
    ];

    $result = $this->setResource->addChecklist($request, '123');

    expect($result)->toBeObject();
});

it('can delete a set', function () {
    $this->mockHandler->append(
        new GuzzleResponse(204, [], '')
    );

    $this->setResource->delete('123');

    expect(true)->toBeTrue(); // If no exception is thrown, the test passes
});
