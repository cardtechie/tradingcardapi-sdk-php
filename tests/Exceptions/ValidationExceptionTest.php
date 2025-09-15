<?php

use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;

it('creates validation exception with default values', function () {
    $exception = new ValidationException;

    expect($exception->getMessage())->toBe('Validation failed');
    expect($exception->getCode())->toBe(422);
    expect($exception->getHttpStatusCode())->toBe(422);
});

it('extracts validation errors by field', function () {
    $apiErrors = [
        [
            'title' => 'Validation Error',
            'detail' => 'Name is required',
            'source' => ['parameter' => 'name'],
        ],
        [
            'title' => 'Validation Error',
            'detail' => 'Email is invalid',
            'source' => ['parameter' => 'email'],
        ],
        [
            'title' => 'General Error',
            'detail' => 'Something went wrong',
        ],
    ];

    $exception = new ValidationException('Validation failed', 422, null, 'validation_failed', $apiErrors);
    $errors = $exception->getValidationErrors();

    expect($errors)->toHaveKey('name', ['Name is required']);
    expect($errors)->toHaveKey('email', ['Email is invalid']);
    expect($errors)->toHaveKey('general', ['Something went wrong']);
});

it('checks if field has validation errors', function () {
    $apiErrors = [
        [
            'title' => 'Validation Error',
            'detail' => 'Name is required',
            'source' => ['parameter' => 'name'],
        ],
    ];

    $exception = new ValidationException('Validation failed', 422, null, 'validation_failed', $apiErrors);

    expect($exception->hasFieldError('name'))->toBeTrue();
    expect($exception->hasFieldError('email'))->toBeFalse();
});

it('gets validation errors for specific field', function () {
    $apiErrors = [
        [
            'title' => 'Validation Error',
            'detail' => 'Name is required',
            'source' => ['parameter' => 'name'],
        ],
        [
            'title' => 'Validation Error',
            'detail' => 'Name is too short',
            'source' => ['parameter' => 'name'],
        ],
    ];

    $exception = new ValidationException('Validation failed', 422, null, 'validation_failed', $apiErrors);
    $nameErrors = $exception->getFieldErrors('name');

    expect($nameErrors)->toHaveCount(2);
    expect($nameErrors)->toContain('Name is required');
    expect($nameErrors)->toContain('Name is too short');
});

it('creates missing required fields exception', function () {
    $exception = ValidationException::missingRequiredFields(['name', 'email'], ['request_id' => '123']);

    expect($exception->getMessage())->toBe('Required fields are missing: name, email');
    expect($exception->getApiErrorCode())->toBe('validation_failed');
    expect($exception->getApiErrors())->toHaveCount(2);
    expect($exception->hasFieldError('name'))->toBeTrue();
    expect($exception->hasFieldError('email'))->toBeTrue();
    expect($exception->getContext())->toBe(['request_id' => '123']);
});

it('creates invalid field values exception', function () {
    $fieldErrors = [
        'name' => 'Name must be at least 3 characters',
        'age' => 'Age must be a positive integer',
    ];

    $exception = ValidationException::invalidFieldValues($fieldErrors, ['user_id' => '456']);

    expect($exception->getMessage())->toBe('Validation failed for provided data');
    expect($exception->getApiErrorCode())->toBe('validation_failed');
    expect($exception->hasFieldError('name'))->toBeTrue();
    expect($exception->hasFieldError('age'))->toBeTrue();
    expect($exception->getFieldErrors('name'))->toContain('Name must be at least 3 characters');
    expect($exception->getContext())->toBe(['user_id' => '456']);
});
