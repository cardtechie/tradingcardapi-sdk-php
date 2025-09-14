<?php

use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;

class TestTradingCardApiException extends TradingCardApiException {}

it('creates exception with all properties', function () {
    $exception = new TestTradingCardApiException(
        'Test message',
        100,
        null,
        'test_error_code',
        [['title' => 'Test Error', 'detail' => 'Test detail']],
        400,
        ['test_context' => 'value']
    );

    expect($exception->getMessage())->toBe('Test message');
    expect($exception->getCode())->toBe(100);
    expect($exception->getApiErrorCode())->toBe('test_error_code');
    expect($exception->getApiErrors())->toBe([['title' => 'Test Error', 'detail' => 'Test detail']]);
    expect($exception->getHttpStatusCode())->toBe(400);
    expect($exception->getContext())->toBe(['test_context' => 'value']);
});

it('gets API error message from errors array', function () {
    $exception = new TestTradingCardApiException(
        'Test message',
        0,
        null,
        null,
        [['title' => 'Test Error', 'detail' => 'Error detail message']]
    );

    expect($exception->getApiErrorMessage())->toBe('Error detail message');
});

it('gets API error message from title when detail is missing', function () {
    $exception = new TestTradingCardApiException(
        'Test message',
        0,
        null,
        null,
        [['title' => 'Error title']]
    );

    expect($exception->getApiErrorMessage())->toBe('Error title');
});

it('returns null when no API errors exist', function () {
    $exception = new TestTradingCardApiException('Test message');

    expect($exception->getApiErrorMessage())->toBeNull();
});

it('identifies client errors correctly', function () {
    $exception = new TestTradingCardApiException('Test', 0, null, null, [], 404);
    expect($exception->isClientError())->toBeTrue();
    expect($exception->isServerError())->toBeFalse();
});

it('identifies server errors correctly', function () {
    $exception = new TestTradingCardApiException('Test', 0, null, null, [], 500);
    expect($exception->isServerError())->toBeTrue();
    expect($exception->isClientError())->toBeFalse();
});

it('converts to array for serialization', function () {
    $exception = new TestTradingCardApiException(
        'Test message',
        100,
        null,
        'test_error',
        [['title' => 'Test']],
        400,
        ['context' => 'value']
    );

    $array = $exception->toArray();

    expect($array)->toHaveKey('message', 'Test message');
    expect($array)->toHaveKey('code', 100);
    expect($array)->toHaveKey('api_error_code', 'test_error');
    expect($array)->toHaveKey('api_errors', [['title' => 'Test']]);
    expect($array)->toHaveKey('http_status_code', 400);
    expect($array)->toHaveKey('context', ['context' => 'value']);
    expect($array)->toHaveKey('file');
    expect($array)->toHaveKey('line');
});
