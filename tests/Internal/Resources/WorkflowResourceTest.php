<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use CardTechie\TradingCardApiSdk\Internal\Resources\Workflow;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\RequestInterface;

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    cache()->put(tokenCacheKey(), 'test-token', 60);

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

it('uses /internal/workflow/actionable-sets URL', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode(['data' => []])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->actionableSets();

    expect((string) $capturedRequest->getUri())->toContain('/internal/workflow/actionable-sets');
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

it('can update a set todo', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-123',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'completed',
                    'completed_at' => '2024-01-01T00:00:00Z',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->updateSetTodo('todo-123', ['status' => 'completed']);

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('todo-123');
    expect($result->data->attributes->status)->toBe('completed');
});

it('uses /internal/set-todos URL for updateSetTodo', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => ['id' => 'todo-123', 'type' => 'set-todos', 'attributes' => ['status' => 'completed']],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->updateSetTodo('todo-123', ['status' => 'completed']);

    expect((string) $capturedRequest->getUri())->toContain('/internal/set-todos/todo-123');
});

it('can bulk initialize workflow', function () {
    $this->mockHandler->append(
        new GuzzleResponse(202, [], json_encode([
            'data' => [
                'job_id' => 'job-abc-123',
                'status' => 'queued',
            ],
        ]))
    );

    $result = $this->workflowResource->bulkInitializeWorkflow(['set_ids' => ['1', '2', '3']]);

    expect($result)->toBeObject();
    expect($result->data->job_id)->toBe('job-abc-123');
    expect($result->data->status)->toBe('queued');
});

it('uses /internal/workflow/bulk-initialize URL', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(202, [], json_encode([
            'data' => ['job_id' => 'job-abc', 'status' => 'queued'],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->bulkInitializeWorkflow(['set_ids' => ['1', '2']]);

    expect($capturedRequest->getMethod())->toBe('POST');
    expect((string) $capturedRequest->getUri())->toContain('/internal/workflow/bulk-initialize');
});

it('can bulk initialize workflow with no params', function () {
    $this->mockHandler->append(
        new GuzzleResponse(202, [], json_encode([
            'data' => [
                'job_id' => 'job-xyz-456',
                'status' => 'queued',
            ],
        ]))
    );

    $result = $this->workflowResource->bulkInitializeWorkflow();

    expect($result)->toBeObject();
    expect($result->data->job_id)->toBe('job-xyz-456');
});

it('can get bulk initialize status', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'job_id' => 'job-abc-123',
                'status' => 'completed',
                'processed' => 150,
                'total' => 150,
            ],
        ]))
    );

    $result = $this->workflowResource->getBulkInitializeStatus('job-abc-123');

    expect($result)->toBeObject();
    expect($result->data->job_id)->toBe('job-abc-123');
    expect($result->data->status)->toBe('completed');
    expect($result->data->processed)->toBe(150);
});

it('uses /internal/workflow/bulk-initialize URL for getBulkInitializeStatus', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => ['job_id' => 'my-job-id', 'status' => 'completed'],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->getBulkInitializeStatus('my-job-id');

    expect((string) $capturedRequest->getUri())->toContain('/internal/workflow/bulk-initialize/my-job-id');
});

it('builds the correct JSON:API envelope for updateSetTodo', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-123',
                'type' => 'set-todos',
                'attributes' => ['status' => 'completed'],
            ],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->updateSetTodo('todo-123', ['status' => 'completed']);

    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['type'])->toBe('set-todos');
    expect($body['data']['id'])->toBe('todo-123');
    expect($body['data']['attributes'])->toBe(['status' => 'completed']);
});

it('throws an exception when updateSetTodo receives a 404', function () {
    $this->mockHandler->append(
        new GuzzleResponse(404, [], json_encode([
            'message' => 'Set todo not found',
        ]))
    );

    expect(fn () => $this->workflowResource->updateSetTodo('nonexistent-id', ['status' => 'completed']))
        ->toThrow(ResourceNotFoundException::class);
});

it('throws an exception when updateSetTodo receives a 422', function () {
    $this->mockHandler->append(
        new GuzzleResponse(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => [
                [
                    'title' => 'Validation Error',
                    'detail' => 'The status field is invalid',
                    'source' => ['parameter' => 'status'],
                ],
            ],
        ]))
    );

    expect(fn () => $this->workflowResource->updateSetTodo('todo-123', ['status' => 'invalid-status']))
        ->toThrow(ValidationException::class);
});

it('can get set todos', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'todos' => [
                [
                    'id' => 'uuid-123',
                    'step' => 'discover_sources',
                    'status' => 'completed',
                    'sort_order' => 0,
                    'started_at' => '2024-03-15T09:00:00+00:00',
                    'completed_at' => '2024-03-15T09:15:00+00:00',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->getSetTodos('set-abc');

    expect($result)->toBeObject();
    expect($result->todos)->toBeArray();
    expect($result->todos)->toHaveCount(1);
    expect($result->todos[0]->id)->toBe('uuid-123');
    expect($result->todos[0]->step)->toBe('discover_sources');
    expect($result->todos[0]->status)->toBe('completed');
});

it('uses /internal/workflow/sets URL for getSetTodos', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode(['todos' => []])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->getSetTodos('set-abc');

    expect((string) $capturedRequest->getUri())->toContain('/internal/workflow/sets/set-abc/todos');
    expect($capturedRequest->getMethod())->toBe('GET');
});

it('throws an exception when getSetTodos receives a 404', function () {
    $this->mockHandler->append(
        new GuzzleResponse(404, [], json_encode([
            'message' => 'Set not found',
        ]))
    );

    expect(fn () => $this->workflowResource->getSetTodos('nonexistent-set-id'))
        ->toThrow(ResourceNotFoundException::class);
});

it('throws an exception when actionableSets receives a 401', function () {
    $this->mockHandler->append(
        new GuzzleResponse(401, [], json_encode([
            'message' => 'Unauthenticated',
        ]))
    );

    expect(fn () => $this->workflowResource->actionableSets())
        ->toThrow(AuthenticationException::class);
});

it('throws an exception when bulkInitializeWorkflow receives a 500', function () {
    $this->mockHandler->append(
        new GuzzleResponse(500, [], json_encode([
            'message' => 'Internal server error',
        ]))
    );

    expect(fn () => $this->workflowResource->bulkInitializeWorkflow())
        ->toThrow(ServerException::class);
});

it('can get review queue', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                [
                    'id' => '1',
                    'type' => 'sets',
                    'attributes' => [
                        'name' => '2024 Topps Baseball',
                        'status' => 'review',
                    ],
                ],
            ],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $result = $resource->getReviewQueue();

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(1);
    expect($result->data[0]->attributes->status)->toBe('review');
    expect((string) $capturedRequest->getUri())->toContain('/internal/workflow/actionable-sets');
    expect((string) $capturedRequest->getUri())->toContain('status=review');
});

it('can flag a todo for review', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-123',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'review',
                    'notes' => 'Data quality issue detected',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->flagForReview('todo-123', 'Data quality issue detected');

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('todo-123');
    expect($result->data->attributes->status)->toBe('review');
    expect($result->data->attributes->notes)->toBe('Data quality issue detected');
});

it('can resolve a review with default notes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-789',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'pending',
                    'notes' => 'Resolved by human review',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->resolveReview('todo-789');

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('todo-789');
    expect($result->data->attributes->status)->toBe('pending');
    expect($result->data->attributes->notes)->toBe('Resolved by human review');
});
