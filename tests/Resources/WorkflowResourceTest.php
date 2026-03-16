<?php

use CardTechie\TradingCardApiSdk\Resources\Workflow;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    cache()->put('tcapi_token_'.md5('test-client-id|test-client-secret'), 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->workflowResource = new Workflow($this->client);
});

it('can be instantiated with client', function () {
    expect($this->workflowResource)->toBeInstanceOf(Workflow::class);
});

it('can get actionable sets', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'sets',
                    'attributes' => [
                        'name' => '2024 Topps Baseball',
                        'status' => 'draft',
                    ],
                ],
                [
                    'id' => '2',
                    'type' => 'sets',
                    'attributes' => [
                        'name' => '2024 Panini Football',
                        'status' => 'draft',
                    ],
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->actionableSets();

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(2);
    expect($result->data[0]->id)->toBe('1');
    expect($result->data[0]->attributes->name)->toBe('2024 Topps Baseball');
    expect($result->data[1]->id)->toBe('2');
    expect($result->data[1]->attributes->name)->toBe('2024 Panini Football');
});

it('can get actionable sets with params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'id' => '3',
                    'type' => 'sets',
                    'attributes' => [
                        'name' => '2024 Topps Baseball',
                        'status' => 'draft',
                        'sport' => 'baseball',
                    ],
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->actionableSets(['filter[sport]' => 'baseball']);

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(1);
    expect($result->data[0]->id)->toBe('3');
    expect($result->data[0]->attributes->sport)->toBe('baseball');
});
