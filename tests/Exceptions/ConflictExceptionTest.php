<?php

use CardTechie\TradingCardApiSdk\Exceptions\ConflictException;

it('creates conflict exception with default values', function () {
    $exception = new ConflictException;

    expect($exception->getMessage())->toBe('Conflict detected');
    expect($exception->getCode())->toBe(409);
    expect($exception->getHttpStatusCode())->toBe(409);
});

it('creates conflict exception with custom message', function () {
    $exception = new ConflictException('Custom conflict message');

    expect($exception->getMessage())->toBe('Custom conflict message');
    expect($exception->getHttpStatusCode())->toBe(409);
});

it('creates duplicate resource exception', function () {
    $exception = ConflictException::duplicate('card', ['card_id' => '123']);

    expect($exception->getMessage())->toBe('Duplicate card detected');
    expect($exception->getApiErrorCode())->toBe('duplicate_resource');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Conflict');
    expect($exception->getContext())->toBe(['card_id' => '123']);
});

it('creates duplicate resource exception without resource type', function () {
    $exception = ConflictException::duplicate('', ['id' => '456']);

    expect($exception->getMessage())->toBe('Duplicate resource detected');
    expect($exception->getApiErrorCode())->toBe('duplicate_resource');
    expect($exception->getContext())->toBe(['id' => '456']);
});

it('has correct HTTP status code', function () {
    $exception = new ConflictException;
    expect($exception->isClientError())->toBeTrue();
    expect($exception->isServerError())->toBeFalse();
});
