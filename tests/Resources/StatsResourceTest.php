<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\CountsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\EntityCount;
use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthMetric;
use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\Snapshot;
use CardTechie\TradingCardApiSdk\DTOs\Stats\SnapshotsResponse;
use CardTechie\TradingCardApiSdk\Resources\Stats;
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
    $this->statsResource = new Stats($this->client);
});

it('can be instantiated with client', function () {
    expect($this->statsResource)->toBeInstanceOf(Stats::class);
});

it('can get cards stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'cards',
                    'unit' => 'daily',
                    'count' => 2,
                    'stats' => [
                        [
                            'date' => '2024-01-15',
                            'count' => 50,
                            'total' => 50,
                        ],
                        [
                            'date' => '2024-01-16',
                            'count' => 100,
                            'total' => 150,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('cards');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('cards');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(2);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(2);
    expect($result->stats[0]->date)->toBe('2024-01-15');
    expect($result->stats[0]->count)->toBe(50);
    expect($result->stats[0]->total)->toBe(50);
    expect($result->stats[1]->date)->toBe('2024-01-16');
    expect($result->stats[1]->count)->toBe(100);
    expect($result->stats[1]->total)->toBe(150);
});

it('can get sets stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'sets',
                    'unit' => 'daily',
                    'count' => 1,
                    'stats' => [
                        [
                            'date' => '2024-01-15',
                            'count' => 25,
                            'total' => 25,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('sets');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('sets');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(1);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(1);
    expect($result->stats[0]->date)->toBe('2024-01-15');
    expect($result->stats[0]->count)->toBe(25);
    expect($result->stats[0]->total)->toBe(25);
});

it('can get players stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'players',
                    'unit' => 'daily',
                    'count' => 3,
                    'stats' => [
                        [
                            'date' => '2024-01-10',
                            'count' => 10,
                            'total' => 10,
                        ],
                        [
                            'date' => '2024-01-11',
                            'count' => 15,
                            'total' => 25,
                        ],
                        [
                            'date' => '2024-01-12',
                            'count' => -5,
                            'total' => 20,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('players');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('players');
    expect($result->unit)->toBe('daily');
    expect($result->count)->toBe(3);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(3);
    expect($result->stats[2]->count)->toBe(-5); // Test negative count (deletions)
    expect($result->stats[2]->total)->toBe(20);
});

it('can get teams stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'teams',
                    'unit' => 'daily',
                    'count' => 1,
                    'stats' => [
                        [
                            'date' => '2024-01-20',
                            'count' => 5,
                            'total' => 5,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('teams');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('teams');
    expect($result->count)->toBe(1);
    expect($result->stats)->toHaveCount(1);
});

it('can get brands stats', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'brands',
                    'unit' => 'daily',
                    'count' => 2,
                    'stats' => [
                        [
                            'date' => '2024-01-18',
                            'count' => 3,
                            'total' => 3,
                        ],
                        [
                            'date' => '2024-01-19',
                            'count' => 7,
                            'total' => 10,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('brands');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('brands');
    expect($result->count)->toBe(2);
    expect($result->stats)->toHaveCount(2);
});

it('handles empty stats response', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'model' => 'manufacturers',
                    'unit' => 'daily',
                    'count' => 0,
                    'stats' => [],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->get('manufacturers');

    expect($result)->toBeInstanceOf(\stdClass::class);
    expect($result->model)->toBe('manufacturers');
    expect($result->count)->toBe(0);
    expect($result->stats)->toBeArray();
    expect($result->stats)->toHaveCount(0);
});

// Tests for getCounts() method
it('can get entity counts', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'counts' => [
                        [
                            'entity_type' => 'sets',
                            'total' => 150,
                            'published' => 120,
                            'draft' => 20,
                            'archived' => 10,
                        ],
                        [
                            'entity_type' => 'cards',
                            'total' => 5000,
                            'published' => 4500,
                            'draft' => 400,
                            'archived' => 100,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getCounts();

    expect($result)->toBeInstanceOf(CountsResponse::class);
    expect($result->counts)->toHaveCount(2);
    expect($result->counts[0])->toBeInstanceOf(EntityCount::class);
    expect($result->counts[0]->entityType)->toBe('sets');
    expect($result->counts[0]->total)->toBe(150);
    expect($result->counts[0]->published)->toBe(120);
    expect($result->counts[0]->draft)->toBe(20);
    expect($result->counts[0]->archived)->toBe(10);
    expect($result->counts[1]->entityType)->toBe('cards');
    expect($result->counts[1]->total)->toBe(5000);
});

it('can get entity count by type', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'counts' => [
                        [
                            'entity_type' => 'sets',
                            'total' => 150,
                            'published' => 120,
                            'draft' => 20,
                            'archived' => 10,
                        ],
                        [
                            'entity_type' => 'cards',
                            'total' => 5000,
                            'published' => 4500,
                            'draft' => 400,
                            'archived' => 100,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getCounts();
    $setsCount = $result->getByEntityType('sets');
    $cardsCount = $result->getByEntityType('cards');
    $unknownCount = $result->getByEntityType('unknown');

    expect($setsCount)->toBeInstanceOf(EntityCount::class);
    expect($setsCount->total)->toBe(150);
    expect($cardsCount)->toBeInstanceOf(EntityCount::class);
    expect($cardsCount->total)->toBe(5000);
    expect($unknownCount)->toBeNull();
});

it('handles empty counts response', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'counts' => [],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getCounts();

    expect($result)->toBeInstanceOf(CountsResponse::class);
    expect($result->counts)->toHaveCount(0);
});

// Tests for getSnapshots() method
it('can get snapshots without filters', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'snapshots' => [
                        [
                            'date' => '2024-11-01',
                            'entity_type' => 'sets',
                            'total' => 100,
                            'published' => 80,
                            'draft' => 15,
                            'archived' => 5,
                        ],
                        [
                            'date' => '2024-11-02',
                            'entity_type' => 'sets',
                            'total' => 105,
                            'published' => 85,
                            'draft' => 15,
                            'archived' => 5,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getSnapshots();

    expect($result)->toBeInstanceOf(SnapshotsResponse::class);
    expect($result->snapshots)->toHaveCount(2);
    expect($result->snapshots[0])->toBeInstanceOf(Snapshot::class);
    expect($result->snapshots[0]->date)->toBe('2024-11-01');
    expect($result->snapshots[0]->entityType)->toBe('sets');
    expect($result->snapshots[0]->total)->toBe(100);
    expect($result->snapshots[0]->published)->toBe(80);
    expect($result->snapshots[1]->date)->toBe('2024-11-02');
    expect($result->snapshots[1]->total)->toBe(105);
});

it('can get snapshots with entity_type filter', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'entity_type' => 'cards',
                    'snapshots' => [
                        [
                            'date' => '2024-11-01',
                            'entity_type' => 'cards',
                            'total' => 5000,
                            'published' => 4500,
                            'draft' => 400,
                            'archived' => 100,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getSnapshots(['entity_type' => 'cards']);

    expect($result)->toBeInstanceOf(SnapshotsResponse::class);
    expect($result->entityType)->toBe('cards');
    expect($result->snapshots)->toHaveCount(1);
    expect($result->snapshots[0]->entityType)->toBe('cards');
});

it('can get snapshots with date range filter', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'from' => '2024-11-01',
                    'to' => '2024-11-30',
                    'snapshots' => [
                        [
                            'date' => '2024-11-15',
                            'entity_type' => 'sets',
                            'total' => 120,
                            'published' => 100,
                            'draft' => 15,
                            'archived' => 5,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getSnapshots([
        'from' => '2024-11-01',
        'to' => '2024-11-30',
    ]);

    expect($result)->toBeInstanceOf(SnapshotsResponse::class);
    expect($result->from)->toBe('2024-11-01');
    expect($result->to)->toBe('2024-11-30');
    expect($result->snapshots)->toHaveCount(1);
});

it('handles empty snapshots response', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'snapshots' => [],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getSnapshots();

    expect($result)->toBeInstanceOf(SnapshotsResponse::class);
    expect($result->snapshots)->toHaveCount(0);
});

// Tests for getGrowth() method
it('can get growth with default period', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'period' => '7d',
                    'metrics' => [
                        [
                            'entity_type' => 'sets',
                            'current' => 150,
                            'previous' => 140,
                            'change' => 10,
                            'percentage_change' => 7.14,
                        ],
                        [
                            'entity_type' => 'cards',
                            'current' => 5000,
                            'previous' => 4800,
                            'change' => 200,
                            'percentage_change' => 4.17,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getGrowth();

    expect($result)->toBeInstanceOf(GrowthResponse::class);
    expect($result->period)->toBe('7d');
    expect($result->metrics)->toHaveCount(2);
    expect($result->metrics[0])->toBeInstanceOf(GrowthMetric::class);
    expect($result->metrics[0]->entityType)->toBe('sets');
    expect($result->metrics[0]->current)->toBe(150);
    expect($result->metrics[0]->previous)->toBe(140);
    expect($result->metrics[0]->change)->toBe(10);
    expect($result->metrics[0]->percentageChange)->toBe(7.14);
});

it('can get growth with custom period', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'period' => '30d',
                    'metrics' => [
                        [
                            'entity_type' => 'sets',
                            'current' => 150,
                            'previous' => 120,
                            'change' => 30,
                            'percentage_change' => 25.0,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getGrowth('30d');

    expect($result)->toBeInstanceOf(GrowthResponse::class);
    expect($result->period)->toBe('30d');
    expect($result->metrics)->toHaveCount(1);
    expect($result->metrics[0]->change)->toBe(30);
    expect($result->metrics[0]->percentageChange)->toBe(25.0);
});

it('can get growth metric by entity type', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'period' => '7d',
                    'metrics' => [
                        [
                            'entity_type' => 'sets',
                            'current' => 150,
                            'previous' => 140,
                            'change' => 10,
                            'percentage_change' => 7.14,
                        ],
                        [
                            'entity_type' => 'cards',
                            'current' => 5000,
                            'previous' => 4800,
                            'change' => 200,
                            'percentage_change' => 4.17,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getGrowth();
    $setsGrowth = $result->getByEntityType('sets');
    $cardsGrowth = $result->getByEntityType('cards');
    $unknownGrowth = $result->getByEntityType('unknown');

    expect($setsGrowth)->toBeInstanceOf(GrowthMetric::class);
    expect($setsGrowth->change)->toBe(10);
    expect($cardsGrowth)->toBeInstanceOf(GrowthMetric::class);
    expect($cardsGrowth->change)->toBe(200);
    expect($unknownGrowth)->toBeNull();
});

it('handles negative growth', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'period' => '7d',
                    'metrics' => [
                        [
                            'entity_type' => 'cards',
                            'current' => 4500,
                            'previous' => 5000,
                            'change' => -500,
                            'percentage_change' => -10.0,
                        ],
                    ],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getGrowth();

    expect($result->metrics[0]->change)->toBe(-500);
    expect($result->metrics[0]->percentageChange)->toBe(-10.0);
});

it('handles empty growth response', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'stats',
                'attributes' => [
                    'period' => '7d',
                    'metrics' => [],
                ],
            ],
        ]))
    );

    $result = $this->statsResource->getGrowth();

    expect($result)->toBeInstanceOf(GrowthResponse::class);
    expect($result->period)->toBe('7d');
    expect($result->metrics)->toHaveCount(0);
});
