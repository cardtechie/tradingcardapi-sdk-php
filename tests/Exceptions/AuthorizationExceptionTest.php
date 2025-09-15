<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthorizationException;

it('creates authorization exception with default values', function () {
    $exception = new AuthorizationException;

    expect($exception->getMessage())->toBe('Access forbidden');
    expect($exception->getCode())->toBe(403);
    expect($exception->getHttpStatusCode())->toBe(403);
});

it('creates authorization exception with custom message', function () {
    $exception = new AuthorizationException('Custom access denied message');

    expect($exception->getMessage())->toBe('Custom access denied message');
    expect($exception->getHttpStatusCode())->toBe(403);
});

it('creates insufficient permissions exception', function () {
    $exception = AuthorizationException::insufficientPermissions('premium content', ['user_id' => '123']);

    expect($exception->getMessage())->toBe('Insufficient permissions to access premium content');
    expect($exception->getApiErrorCode())->toBe('insufficient_permissions');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Insufficient Permissions');
    expect($exception->getContext())->toBe(['user_id' => '123']);
});

it('creates insufficient permissions exception without resource', function () {
    $exception = AuthorizationException::insufficientPermissions('', ['user_id' => '456']);

    expect($exception->getMessage())->toBe('Insufficient permissions');
    expect($exception->getApiErrorCode())->toBe('insufficient_permissions');
    expect($exception->getContext())->toBe(['user_id' => '456']);
});

it('creates account suspended exception', function () {
    $exception = AuthorizationException::accountSuspended(['admin_id' => 'admin123']);

    expect($exception->getMessage())->toBe('Account has been suspended');
    expect($exception->getApiErrorCode())->toBe('account_suspended');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Account Suspended');
    expect($exception->getContext())->toBe(['admin_id' => 'admin123']);
});

it('has correct HTTP status code', function () {
    $exception = new AuthorizationException;
    expect($exception->isClientError())->toBeTrue();
    expect($exception->isServerError())->toBeFalse();
});
