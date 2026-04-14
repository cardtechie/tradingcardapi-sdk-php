<?php

use CardTechie\TradingCardApiSdk\Models\AuditLog as AuditLogModel;
use CardTechie\TradingCardApiSdk\Resources\AuditLog;
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
    cache()->put(tokenCacheKey(), 'test-token', 60);

    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->client = new Client(['handler' => $handlerStack]);
    $this->auditLogResource = new AuditLog($this->client);
});

it('can be instantiated with client', function () {
    expect($this->auditLogResource)->toBeInstanceOf(AuditLog::class);
});

it('can get a list of audit logs', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'audit-logs',
                    'id' => '1',
                    'attributes' => [
                        'auditable_type' => 'Set',
                        'auditable_id' => 'set-123',
                        'event_type' => 'created',
                        'created_at' => '2026-04-13T10:00:00Z',
                    ],
                ],
                [
                    'type' => 'audit-logs',
                    'id' => '2',
                    'attributes' => [
                        'auditable_type' => 'Card',
                        'auditable_id' => 'card-456',
                        'event_type' => 'updated',
                        'created_at' => '2026-04-13T11:00:00Z',
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

    $result = $this->auditLogResource->getAuditLogs();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can get audit logs with filters', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'audit-logs',
                    'id' => '1',
                    'attributes' => [
                        'auditable_type' => 'Set',
                        'auditable_id' => 'set-123',
                        'event_type' => 'created',
                        'created_at' => '2026-04-13T10:00:00Z',
                    ],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 1,
                    'per_page' => 25,
                    'current_page' => 1,
                ],
            ],
        ]))
    );

    $params = [
        'auditable_type' => 'Set',
        'event_type' => 'created',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-13',
        'per_page' => 25,
    ];
    $result = $this->auditLogResource->getAuditLogs($params);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('can create an audit event', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'audit-logs',
                'id' => '1',
                'attributes' => [
                    'auditable_type' => 'Set',
                    'auditable_id' => 'set-123',
                    'event_type' => 'manual_review',
                    'description' => 'Manual review completed',
                    'created_at' => '2026-04-13T12:00:00Z',
                ],
            ],
        ]))
    );

    $attributes = [
        'auditable_type' => 'Set',
        'auditable_id' => 'set-123',
        'event_type' => 'manual_review',
        'description' => 'Manual review completed',
    ];

    $result = $this->auditLogResource->createAuditEvent($attributes);

    expect($result)->toBeInstanceOf(AuditLogModel::class);
});

it('can create an audit event without attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'audit-logs',
                'id' => '1',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->auditLogResource->createAuditEvent();

    expect($result)->toBeInstanceOf(AuditLogModel::class);
});
