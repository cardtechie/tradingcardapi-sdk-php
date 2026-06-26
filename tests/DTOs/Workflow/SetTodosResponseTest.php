<?php

use CardTechie\TradingCardApiSdk\DTOs\Workflow\SetTodo;
use CardTechie\TradingCardApiSdk\DTOs\Workflow\SetTodosResponse;

it('can create SetTodosResponse from a response object', function () {
    $response = (object) [
        'todos' => [
            (object) [
                'id' => 'uuid-123',
                'step' => 'discover_sources',
                'status' => 'completed',
                'sort_order' => 0,
                'started_at' => '2024-03-15T09:00:00+00:00',
                'completed_at' => '2024-03-15T09:15:00+00:00',
            ],
        ],
    ];

    $result = SetTodosResponse::fromResponse($response);

    expect($result)->toBeInstanceOf(SetTodosResponse::class);
    expect($result->todos)->toHaveCount(1);
    expect($result->todos[0])->toBeInstanceOf(SetTodo::class);
    expect($result->todos[0]->id)->toBe('uuid-123');
    expect($result->todos[0]->step)->toBe('discover_sources');
    expect($result->todos[0]->status)->toBe('completed');
    expect($result->todos[0]->sortOrder)->toBe(0);
    expect($result->todos[0]->startedAt)->toBe('2024-03-15T09:00:00+00:00');
    expect($result->todos[0]->completedAt)->toBe('2024-03-15T09:15:00+00:00');
});

it('handles an empty todos array', function () {
    $response = (object) ['todos' => []];

    $result = SetTodosResponse::fromResponse($response);

    expect($result->todos)->toHaveCount(0);
});

it('handles a missing todos key', function () {
    $response = (object) [];

    $result = SetTodosResponse::fromResponse($response);

    expect($result->todos)->toHaveCount(0);
});

it('defaults optional todo fields to null', function () {
    $response = (object) [
        'todos' => [
            (object) ['id' => 'uuid-456', 'step' => 'validate', 'status' => 'pending'],
        ],
    ];

    $result = SetTodosResponse::fromResponse($response);

    expect($result->todos[0]->sortOrder)->toBeNull();
    expect($result->todos[0]->startedAt)->toBeNull();
    expect($result->todos[0]->completedAt)->toBeNull();
});
