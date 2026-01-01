<?php

use CardTechie\TradingCardApiSdk\DTOs\Stats\Snapshot;
use CardTechie\TradingCardApiSdk\DTOs\Stats\SnapshotsResponse;

it('can create SnapshotsResponse from response object', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'entity_type' => 'cards',
                'from' => '2024-11-01',
                'to' => '2024-11-30',
                'snapshots' => [
                    (object) [
                        'date' => '2024-11-15',
                        'entity_type' => 'cards',
                        'total' => 5000,
                        'published' => 4500,
                        'draft' => 400,
                        'archived' => 100,
                    ],
                    (object) [
                        'date' => '2024-11-20',
                        'entity_type' => 'cards',
                        'total' => 5100,
                        'published' => 4600,
                        'draft' => 400,
                        'archived' => 100,
                    ],
                ],
            ],
        ],
    ];

    $snapshotsResponse = SnapshotsResponse::fromResponse($response);

    expect($snapshotsResponse)->toBeInstanceOf(SnapshotsResponse::class);
    expect($snapshotsResponse->snapshots)->toHaveCount(2);
    expect($snapshotsResponse->entityType)->toBe('cards');
    expect($snapshotsResponse->from)->toBe('2024-11-01');
    expect($snapshotsResponse->to)->toBe('2024-11-30');
    expect($snapshotsResponse->snapshots[0])->toBeInstanceOf(Snapshot::class);
});

it('handles response with missing optional attributes', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'snapshots' => [
                    (object) [
                        'date' => '2024-11-15',
                        'entity_type' => 'sets',
                        'total' => 150,
                        'published' => 120,
                        'draft' => 20,
                        'archived' => 10,
                    ],
                ],
            ],
        ],
    ];

    $snapshotsResponse = SnapshotsResponse::fromResponse($response);

    expect($snapshotsResponse->snapshots)->toHaveCount(1);
    expect($snapshotsResponse->entityType)->toBeNull();
    expect($snapshotsResponse->from)->toBeNull();
    expect($snapshotsResponse->to)->toBeNull();
});

it('handles response with missing snapshots array', function () {
    $response = (object) [
        'data' => (object) [
            'attributes' => (object) [
                'entity_type' => 'cards',
            ],
        ],
    ];

    $snapshotsResponse = SnapshotsResponse::fromResponse($response);

    expect($snapshotsResponse->snapshots)->toHaveCount(0);
    expect($snapshotsResponse->entityType)->toBe('cards');
});

it('handles response with missing attributes', function () {
    $response = (object) [
        'data' => (object) [],
    ];

    $snapshotsResponse = SnapshotsResponse::fromResponse($response);

    expect($snapshotsResponse->snapshots)->toHaveCount(0);
    expect($snapshotsResponse->entityType)->toBeNull();
    expect($snapshotsResponse->from)->toBeNull();
    expect($snapshotsResponse->to)->toBeNull();
});
