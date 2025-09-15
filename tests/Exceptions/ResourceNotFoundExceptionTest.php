<?php

use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;

it('creates resource not found exception with default values', function () {
    $exception = new ResourceNotFoundException;

    expect($exception->getMessage())->toBe('Resource not found');
    expect($exception->getCode())->toBe(404);
    expect($exception->getHttpStatusCode())->toBe(404);
});

it('creates resource not found exception with custom message', function () {
    $exception = new ResourceNotFoundException('Custom not found message');

    expect($exception->getMessage())->toBe('Custom not found message');
    expect($exception->getHttpStatusCode())->toBe(404);
});

it('creates resource exception with type and ID', function () {
    $exception = ResourceNotFoundException::resource('product', 'abc123', ['shop_id' => '456']);

    expect($exception->getMessage())->toBe("The product with ID 'abc123' was not found");
    expect($exception->getApiErrorCode())->toBe('resource_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Resource Not Found');
    expect($exception->getApiErrors()[0]['source']['parameter'])->toBe('id');
    expect($exception->getContext())->toHaveKey('resource_type', 'product');
    expect($exception->getContext())->toHaveKey('resource_id', 'abc123');
    expect($exception->getContext())->toHaveKey('shop_id', '456');
});

it('creates endpoint not found exception', function () {
    $exception = ResourceNotFoundException::endpoint('/api/v2/invalid', ['request_id' => 'req123']);

    expect($exception->getMessage())->toBe("The endpoint '/api/v2/invalid' was not found");
    expect($exception->getApiErrorCode())->toBe('endpoint_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Endpoint Not Found');
    expect($exception->getApiErrors()[0]['detail'])->toBe('The requested endpoint does not exist');
    expect($exception->getContext())->toHaveKey('endpoint', '/api/v2/invalid');
    expect($exception->getContext())->toHaveKey('request_id', 'req123');
});

it('has correct HTTP status code', function () {
    $exception = new ResourceNotFoundException;
    expect($exception->isClientError())->toBeTrue();
    expect($exception->isServerError())->toBeFalse();
});
