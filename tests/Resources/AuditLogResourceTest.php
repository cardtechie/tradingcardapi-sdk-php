<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use CardTechie\TradingCardApiSdk\Models\AuditLog as AuditLogModel;
use CardTechie\TradingCardApiSdk\Resources\AuditLog;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Psr\Http\Message\RequestInterface;

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

// --- paginator property assertions ---

it('returns paginator with correct total, perPage, and currentPage from meta', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'audit-logs',
                    'id' => '1',
                    'attributes' => ['event_type' => 'created'],
                ],
            ],
            'meta' => [
                'pagination' => [
                    'total' => 200,
                    'per_page' => 25,
                    'current_page' => 3,
                ],
            ],
        ]))
    );

    $result = $this->auditLogResource->getAuditLogs(['per_page' => 25, 'page' => 3]);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(200);
    expect($result->perPage())->toBe(25);
    expect($result->currentPage())->toBe(3);
});

// --- missing meta fallback path ---

it('falls back to count-based totals when meta is absent', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'type' => 'audit-logs',
                    'id' => '1',
                    'attributes' => ['event_type' => 'created'],
                ],
                [
                    'type' => 'audit-logs',
                    'id' => '2',
                    'attributes' => ['event_type' => 'updated'],
                ],
            ],
        ]))
    );

    $result = $this->auditLogResource->getAuditLogs();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    // total falls back to count($response->data) = 2
    expect($result->total())->toBe(2);
    // perPage and currentPage fall back to default params
    expect($result->perPage())->toBe(50);
    expect($result->currentPage())->toBe(1);
});

// --- default query params are sent ---

it('sends default per_page and page params in the query string', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
            'meta' => ['pagination' => ['total' => 0, 'per_page' => 50, 'current_page' => 1]],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new AuditLog($client);

    $resource->getAuditLogs();

    $uri = (string) $capturedRequest->getUri();
    expect($uri)->toContain('per_page=50');
    expect($uri)->toContain('page=1');
    expect($capturedRequest->getMethod())->toBe('GET');
});

// --- filter params are forwarded ---

it('includes filter params in the query string', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
            'meta' => ['pagination' => ['total' => 0, 'per_page' => 25, 'current_page' => 1]],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new AuditLog($client);

    $resource->getAuditLogs([
        'auditable_type' => 'Set',
        'event_type' => 'created',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-13',
        'per_page' => 25,
    ]);

    $uri = (string) $capturedRequest->getUri();
    expect($uri)->toContain('auditable_type=Set');
    expect($uri)->toContain('event_type=created');
    expect($uri)->toContain('start_date=2026-04-01');
    expect($uri)->toContain('end_date=2026-04-13');
    expect($uri)->toContain('per_page=25');
});

// --- createAuditEvent request shape ---

it('builds the correct JSON:API envelope for createAuditEvent with attributes', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'audit-logs',
                'id' => '99',
                'attributes' => ['event_type' => 'manual_review'],
            ],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new AuditLog($client);

    $resource->createAuditEvent(['event_type' => 'manual_review', 'auditable_id' => 'set-1']);

    expect($capturedRequest->getMethod())->toBe('POST');
    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['type'])->toBe('audit-logs');
    expect($body['data']['attributes'])->toBe(['event_type' => 'manual_review', 'auditable_id' => 'set-1']);
});

it('omits attributes key from JSON:API envelope when createAuditEvent is called with no attributes', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'type' => 'audit-logs',
                'id' => '1',
                'attributes' => [],
            ],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new AuditLog($client);

    $resource->createAuditEvent();

    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['type'])->toBe('audit-logs');
    expect($body['data'])->not->toHaveKey('attributes');
});

// --- error handling for getAuditLogs ---

it('throws AuthenticationException when getAuditLogs receives a 401', function () {
    $this->mockHandler->append(
        new GuzzleResponse(401, [], json_encode([
            'message' => 'Unauthenticated',
        ]))
    );

    expect(fn () => $this->auditLogResource->getAuditLogs())
        ->toThrow(AuthenticationException::class);
});

it('throws ResourceNotFoundException when getAuditLogs receives a 404', function () {
    $this->mockHandler->append(
        new GuzzleResponse(404, [], json_encode([
            'message' => 'Not found',
        ]))
    );

    expect(fn () => $this->auditLogResource->getAuditLogs())
        ->toThrow(ResourceNotFoundException::class);
});

it('throws ServerException when getAuditLogs receives a 500', function () {
    $this->mockHandler->append(
        new GuzzleResponse(500, [], json_encode([
            'message' => 'Internal server error',
        ]))
    );

    expect(fn () => $this->auditLogResource->getAuditLogs())
        ->toThrow(ServerException::class);
});

// --- error handling for createAuditEvent ---

it('throws ValidationException when createAuditEvent receives a 422', function () {
    $this->mockHandler->append(
        new GuzzleResponse(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => [
                [
                    'title' => 'Validation Error',
                    'detail' => 'The event_type field is required',
                    'source' => ['parameter' => 'event_type'],
                ],
            ],
        ]))
    );

    expect(fn () => $this->auditLogResource->createAuditEvent(['auditable_id' => 'set-1']))
        ->toThrow(ValidationException::class);
});

it('throws AuthenticationException when createAuditEvent receives a 401', function () {
    $this->mockHandler->append(
        new GuzzleResponse(401, [], json_encode([
            'message' => 'Unauthenticated',
        ]))
    );

    expect(fn () => $this->auditLogResource->createAuditEvent(['event_type' => 'manual_review']))
        ->toThrow(AuthenticationException::class);
});

it('throws ServerException when createAuditEvent receives a 500', function () {
    $this->mockHandler->append(
        new GuzzleResponse(500, [], json_encode([
            'message' => 'Internal server error',
        ]))
    );

    expect(fn () => $this->auditLogResource->createAuditEvent())
        ->toThrow(ServerException::class);
});
