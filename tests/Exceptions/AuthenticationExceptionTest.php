<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;

it('creates authentication exception with default values', function () {
    $exception = new AuthenticationException;

    expect($exception->getMessage())->toBe('Authentication failed');
    expect($exception->getCode())->toBe(401);
    expect($exception->getHttpStatusCode())->toBe(401);
});

it('creates authentication exception with custom message', function () {
    $exception = new AuthenticationException('Custom auth message');

    expect($exception->getMessage())->toBe('Custom auth message');
    expect($exception->getHttpStatusCode())->toBe(401);
});

it('creates invalid credentials exception', function () {
    $exception = AuthenticationException::invalidCredentials(['client_id' => 'test']);

    expect($exception->getMessage())->toBe('Invalid client credentials provided');
    expect($exception->getApiErrorCode())->toBe('invalid_credentials');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Invalid Credentials');
    expect($exception->getContext())->toBe(['client_id' => 'test']);
});

it('creates expired token exception', function () {
    $exception = AuthenticationException::expiredToken(['token' => 'abc123']);

    expect($exception->getMessage())->toBe('Access token has expired');
    expect($exception->getApiErrorCode())->toBe('token_expired');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Token Expired');
    expect($exception->getContext())->toBe(['token' => 'abc123']);
});

it('has correct HTTP status code', function () {
    $exception = new AuthenticationException;
    expect($exception->isClientError())->toBeTrue();
    expect($exception->isServerError())->toBeFalse();
});
