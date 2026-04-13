<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use CardTechie\TradingCardApiSdk\Resources\Workflow;
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

// --- updateSetTodo edge cases ---

it('can update a set todo with multiple attributes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-456',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'notes' => 'Started processing',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->updateSetTodo('todo-456', [
        'status' => 'in_progress',
        'priority' => 'high',
        'notes' => 'Started processing',
    ]);

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('todo-456');
    expect($result->data->attributes->status)->toBe('in_progress');
    expect($result->data->attributes->priority)->toBe('high');
    expect($result->data->attributes->notes)->toBe('Started processing');
});

it('can update a set todo with an empty attributes array', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-789',
                'type' => 'set-todos',
                'attributes' => [],
            ],
        ]))
    );

    $result = $this->workflowResource->updateSetTodo('todo-789', []);

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('todo-789');
    expect($result->data->type)->toBe('set-todos');
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

// --- bulkInitializeWorkflow edge cases ---

it('can bulk initialize workflow and returns processing status', function () {
    $this->mockHandler->append(
        new GuzzleResponse(202, [], json_encode([
            'data' => [
                'job_id' => 'job-processing-789',
                'status' => 'processing',
                'processed' => 50,
                'total' => 200,
            ],
        ]))
    );

    $result = $this->workflowResource->bulkInitializeWorkflow(['set_ids' => ['10', '11']]);

    expect($result)->toBeObject();
    expect($result->data->status)->toBe('processing');
    expect($result->data->processed)->toBe(50);
    expect($result->data->total)->toBe(200);
});

it('throws an exception when bulkInitializeWorkflow receives a 422', function () {
    $this->mockHandler->append(
        new GuzzleResponse(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => [
                [
                    'title' => 'Validation Error',
                    'detail' => 'set_ids must be an array',
                    'source' => ['parameter' => 'set_ids'],
                ],
            ],
        ]))
    );

    expect(fn () => $this->workflowResource->bulkInitializeWorkflow(['set_ids' => 'not-an-array']))
        ->toThrow(ValidationException::class);
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

// --- getBulkInitializeStatus edge cases ---

it('can get bulk initialize status when job is still processing', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'job_id' => 'job-abc-123',
                'status' => 'processing',
                'processed' => 75,
                'total' => 150,
            ],
        ]))
    );

    $result = $this->workflowResource->getBulkInitializeStatus('job-abc-123');

    expect($result)->toBeObject();
    expect($result->data->status)->toBe('processing');
    expect($result->data->processed)->toBe(75);
    expect($result->data->total)->toBe(150);
});

it('can get bulk initialize status when job has failed', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'job_id' => 'job-failed-001',
                'status' => 'failed',
                'processed' => 10,
                'total' => 150,
                'error' => 'Unexpected error during processing',
            ],
        ]))
    );

    $result = $this->workflowResource->getBulkInitializeStatus('job-failed-001');

    expect($result)->toBeObject();
    expect($result->data->status)->toBe('failed');
    expect($result->data->error)->toBe('Unexpected error during processing');
});

it('throws an exception when getBulkInitializeStatus receives a 404 for unknown job', function () {
    $this->mockHandler->append(
        new GuzzleResponse(404, [], json_encode([
            'message' => 'Job not found',
        ]))
    );

    expect(fn () => $this->workflowResource->getBulkInitializeStatus('nonexistent-job-id'))
        ->toThrow(ResourceNotFoundException::class);
});

it('builds the correct URL for getBulkInitializeStatus', function () {
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

    expect((string) $capturedRequest->getUri())->toContain('/v1/workflow/bulk-initialize/my-job-id');
});

it('sends the correct method, URL, and body for bulkInitializeWorkflow', function () {
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
    expect((string) $capturedRequest->getUri())->toContain('/v1/workflow/bulk-initialize');
    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['set_ids'])->toBe(['1', '2']);
});

// --- actionableSets edge cases ---

it('can get actionable sets and returns an empty list', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ]))
    );

    $result = $this->workflowResource->actionableSets();

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(0);
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

// --- getSetTodos ---

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

it('can get set todos when set has no initialized workflow', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'todos' => [],
        ]))
    );

    $result = $this->workflowResource->getSetTodos('set-no-workflow');

    expect($result)->toBeObject();
    expect($result->todos)->toBeArray();
    expect($result->todos)->toHaveCount(0);
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

it('builds the correct URL for getSetTodos', function () {
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

    expect((string) $capturedRequest->getUri())->toContain('/v1/workflow/sets/set-abc/todos');
    expect($capturedRequest->getMethod())->toBe('GET');
});

it('can get set todos returns multiple todos with correct structure', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'todos' => [
                [
                    'id' => 'uuid-001',
                    'step' => 'discover_sources',
                    'status' => 'completed',
                    'sort_order' => 0,
                    'started_at' => '2024-03-15T09:00:00+00:00',
                    'completed_at' => '2024-03-15T09:15:00+00:00',
                ],
                [
                    'id' => 'uuid-002',
                    'step' => 'import_cards',
                    'status' => 'pending',
                    'sort_order' => 1,
                    'started_at' => null,
                    'completed_at' => null,
                ],
                [
                    'id' => 'uuid-003',
                    'step' => 'review_cards',
                    'status' => 'pending',
                    'sort_order' => 2,
                    'started_at' => null,
                    'completed_at' => null,
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->getSetTodos('set-multi');

    expect($result)->toBeObject();
    expect($result->todos)->toBeArray();
    expect($result->todos)->toHaveCount(3);
    expect($result->todos[0]->id)->toBe('uuid-001');
    expect($result->todos[0]->status)->toBe('completed');
    expect($result->todos[1]->id)->toBe('uuid-002');
    expect($result->todos[1]->step)->toBe('import_cards');
    expect($result->todos[2]->sort_order)->toBe(2);
});

it('throws an exception when getSetTodos receives a 401', function () {
    $this->mockHandler->append(
        new GuzzleResponse(401, [], json_encode([
            'message' => 'Unauthenticated',
        ]))
    );

    expect(fn () => $this->workflowResource->getSetTodos('set-abc'))
        ->toThrow(AuthenticationException::class);
});

it('throws an exception when getSetTodos receives a 500', function () {
    $this->mockHandler->append(
        new GuzzleResponse(500, [], json_encode([
            'message' => 'Internal server error',
        ]))
    );

    expect(fn () => $this->workflowResource->getSetTodos('set-abc'))
        ->toThrow(ServerException::class);
});

// --- getReviewQueue ---

it('can get review queue', function () {
    $this->mockHandler->append(
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
        ]))
    );

    $result = $this->workflowResource->getReviewQueue();

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(1);
    expect($result->data[0]->attributes->status)->toBe('review');
});

it('can get review queue filtered by step', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->getReviewQueue('parse');

    $uri = (string) $capturedRequest->getUri();
    expect($uri)->toContain('status=review');
    expect($uri)->toContain('step=parse');
});

it('can get review queue with additional params', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ])),
    ]);

    $middleware = Middleware::tap(function (RequestInterface $request) use (&$capturedRequest) {
        $capturedRequest = $request;
    });

    $handlerStack = HandlerStack::create($customHandler);
    $handlerStack->push($middleware);
    $client = new Client(['handler' => $handlerStack]);
    $resource = new Workflow($client);

    $resource->getReviewQueue(null, ['filter[sport]' => 'baseball']);

    $uri = (string) $capturedRequest->getUri();
    expect($uri)->toContain('status=review');
    expect($uri)->toContain('filter%5Bsport%5D=baseball');
    expect($uri)->not->toContain('step=');
});

it('can get review queue and returns an empty list', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [],
        ]))
    );

    $result = $this->workflowResource->getReviewQueue();

    expect($result)->toBeObject();
    expect($result->data)->toBeArray();
    expect($result->data)->toHaveCount(0);
});

// --- flagForReview ---

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

it('builds the correct JSON:API envelope for flagForReview', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-456',
                'type' => 'set-todos',
                'attributes' => ['status' => 'review', 'notes' => 'Needs review'],
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

    $resource->flagForReview('todo-456', 'Needs review');

    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['type'])->toBe('set-todos');
    expect($body['data']['id'])->toBe('todo-456');
    expect($body['data']['attributes']['status'])->toBe('review');
    expect($body['data']['attributes']['notes'])->toBe('Needs review');
});

// --- resolveReview ---

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

it('can resolve a review with custom notes', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-789',
                'type' => 'set-todos',
                'attributes' => [
                    'status' => 'pending',
                    'notes' => 'Verified card data is correct',
                ],
            ],
        ]))
    );

    $result = $this->workflowResource->resolveReview('todo-789', 'Verified card data is correct');

    expect($result)->toBeObject();
    expect($result->data->attributes->status)->toBe('pending');
    expect($result->data->attributes->notes)->toBe('Verified card data is correct');
});

it('builds the correct JSON:API envelope for resolveReview with default notes', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-789',
                'type' => 'set-todos',
                'attributes' => ['status' => 'pending', 'notes' => 'Resolved by human review'],
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

    $resource->resolveReview('todo-789');

    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['type'])->toBe('set-todos');
    expect($body['data']['id'])->toBe('todo-789');
    expect($body['data']['attributes']['status'])->toBe('pending');
    expect($body['data']['attributes']['notes'])->toBe('Resolved by human review');
});

it('builds the correct JSON:API envelope for resolveReview with custom notes', function () {
    $capturedRequest = null;

    $customHandler = new MockHandler([
        new GuzzleResponse(200, [], json_encode([
            'data' => [
                'id' => 'todo-789',
                'type' => 'set-todos',
                'attributes' => ['status' => 'pending', 'notes' => 'Custom note'],
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

    $resource->resolveReview('todo-789', 'Custom note');

    $body = json_decode((string) $capturedRequest->getBody(), true);
    expect($body['data']['attributes']['status'])->toBe('pending');
    expect($body['data']['attributes']['notes'])->toBe('Custom note');
});
