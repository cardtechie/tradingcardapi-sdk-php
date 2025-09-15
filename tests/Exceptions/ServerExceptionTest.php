<?php

use CardTechie\TradingCardApiSdk\Exceptions\ServerException;

it('creates server exception with default values', function () {
    $exception = new ServerException();

    expect($exception->getMessage())->toBe('Internal server error');
    expect($exception->getCode())->toBe(500);
    expect($exception->getHttpStatusCode())->toBe(500);
});

it('creates server exception with custom values', function () {
    $exception = new ServerException('Custom server error', 503, null, 'custom_error', [], 503, ['server_id' => 'srv123']);

    expect($exception->getMessage())->toBe('Custom server error');
    expect($exception->getCode())->toBe(503);
    expect($exception->getHttpStatusCode())->toBe(503);
    expect($exception->getApiErrorCode())->toBe('custom_error');
    expect($exception->getContext())->toBe(['server_id' => 'srv123']);
});

it('creates internal server error exception', function () {
    $exception = ServerException::internalServerError(['trace_id' => 'trace123']);

    expect($exception->getMessage())->toBe('The server encountered an internal error');
    expect($exception->getCode())->toBe(500);
    expect($exception->getHttpStatusCode())->toBe(500);
    expect($exception->getApiErrorCode())->toBe('internal_server_error');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Internal Server Error');
    expect($exception->getContext())->toBe(['trace_id' => 'trace123']);
});

it('creates service unavailable exception', function () {
    $exception = ServerException::serviceUnavailable(['maintenance' => true]);

    expect($exception->getMessage())->toBe('Service temporarily unavailable');
    expect($exception->getCode())->toBe(503);
    expect($exception->getHttpStatusCode())->toBe(503);
    expect($exception->getApiErrorCode())->toBe('service_unavailable');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Service Unavailable');
    expect($exception->getContext())->toBe(['maintenance' => true]);
});

it('creates bad gateway exception', function () {
    $exception = ServerException::badGateway(['upstream' => 'api.upstream.com']);

    expect($exception->getMessage())->toBe('Bad gateway response');
    expect($exception->getCode())->toBe(502);
    expect($exception->getHttpStatusCode())->toBe(502);
    expect($exception->getApiErrorCode())->toBe('bad_gateway');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Bad Gateway');
    expect($exception->getContext())->toBe(['upstream' => 'api.upstream.com']);
});

it('creates gateway timeout exception', function () {
    $exception = ServerException::gatewayTimeout(['timeout_duration' => '30s']);

    expect($exception->getMessage())->toBe('Gateway timeout');
    expect($exception->getCode())->toBe(504);
    expect($exception->getHttpStatusCode())->toBe(504);
    expect($exception->getApiErrorCode())->toBe('gateway_timeout');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Gateway Timeout');
    expect($exception->getContext())->toBe(['timeout_duration' => '30s']);
});

it('has correct HTTP status code classification', function () {
    $exception = new ServerException();
    expect($exception->isServerError())->toBeTrue();
    expect($exception->isClientError())->toBeFalse();

    $exception503 = ServerException::serviceUnavailable();
    expect($exception503->isServerError())->toBeTrue();
    expect($exception503->isClientError())->toBeFalse();
});