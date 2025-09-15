<?php

use CardTechie\TradingCardApiSdk\Exceptions\CardNotFoundException;

it('creates card not found exception by ID', function () {
    $exception = CardNotFoundException::byId('card123', ['user_id' => 'user456']);

    expect($exception->getMessage())->toBe("The card with ID 'card123' was not found");
    expect($exception->getCode())->toBe(404);
    expect($exception->getHttpStatusCode())->toBe(404);
    expect($exception->getApiErrorCode())->toBe('card_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Card Not Found');
    expect($exception->getContext())->toHaveKey('resource_type', 'card');
    expect($exception->getContext())->toHaveKey('resource_id', 'card123');
    expect($exception->getContext())->toHaveKey('user_id', 'user456');
});

it('creates card not found exception by criteria', function () {
    $criteria = ['name' => 'Pikachu', 'set' => 'Base Set'];
    $exception = CardNotFoundException::byCriteria($criteria, ['search_id' => 'search123']);

    expect($exception->getMessage())->toBe('No card found matching criteria: name=Pikachu, set=Base Set');
    expect($exception->getApiErrorCode())->toBe('card_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Card Not Found');
    expect($exception->getApiErrors()[0]['detail'])->toBe('No card found matching the specified criteria');
    expect($exception->getContext())->toHaveKey('criteria', $criteria);
    expect($exception->getContext())->toHaveKey('search_id', 'search123');
});

it('inherits from ResourceNotFoundException', function () {
    $exception = CardNotFoundException::byId('test123');
    expect($exception)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException::class);
});