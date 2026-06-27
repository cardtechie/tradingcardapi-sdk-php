<?php

use CardTechie\TradingCardApiSdk\Services\Redactor;

beforeEach(function () {
    $this->redactor = new Redactor;
});

it('redacts the Authorization header', function () {
    $headers = [
        'Authorization' => 'Bearer super-secret-token',
        'Content-Type' => 'application/json',
    ];

    $redacted = $this->redactor->redactHeaders($headers);

    expect($redacted['Authorization'])->toBe(Redactor::REDACTED);
    expect($redacted['Content-Type'])->toBe('application/json');
});

it('redacts sensitive headers case-insensitively', function () {
    $headers = [
        'authorization' => 'Bearer token',
        'Proxy-Authorization' => 'Basic abc',
        'X-API-Key' => 'key-123',
    ];

    $redacted = $this->redactor->redactHeaders($headers);

    expect($redacted['authorization'])->toBe(Redactor::REDACTED);
    expect($redacted['Proxy-Authorization'])->toBe(Redactor::REDACTED);
    expect($redacted['X-API-Key'])->toBe(Redactor::REDACTED);
});

it('redacts client_secret and access_token at the top level', function () {
    $payload = [
        'client_secret' => 'shhh',
        'access_token' => 'token-abc',
        'grant_type' => 'client_credentials',
    ];

    $redacted = $this->redactor->redact($payload);

    expect($redacted['client_secret'])->toBe(Redactor::REDACTED);
    expect($redacted['access_token'])->toBe(Redactor::REDACTED);
    expect($redacted['grant_type'])->toBe('client_credentials');
});

it('redacts sensitive keys at any nesting depth', function () {
    $payload = [
        'data' => [
            'attributes' => [
                'client_secret' => 'deep-secret',
                'refresh_token' => 'deep-refresh',
                'name' => 'Test Card',
            ],
        ],
        'meta' => [
            'access_token' => 'meta-token',
        ],
    ];

    $redacted = $this->redactor->redact($payload);

    expect($redacted['data']['attributes']['client_secret'])->toBe(Redactor::REDACTED);
    expect($redacted['data']['attributes']['refresh_token'])->toBe(Redactor::REDACTED);
    expect($redacted['data']['attributes']['name'])->toBe('Test Card');
    expect($redacted['meta']['access_token'])->toBe(Redactor::REDACTED);
});

it('redacts sensitive keys case-insensitively', function () {
    $payload = [
        'Client_Secret' => 'one',
        'ACCESS_TOKEN' => 'two',
        'Password' => 'three',
    ];

    $redacted = $this->redactor->redact($payload);

    expect($redacted['Client_Secret'])->toBe(Redactor::REDACTED);
    expect($redacted['ACCESS_TOKEN'])->toBe(Redactor::REDACTED);
    expect($redacted['Password'])->toBe(Redactor::REDACTED);
});

it('masks bearer tokens embedded in string values', function () {
    $payload = [
        'note' => 'Request used Bearer eyJ0eXAiOiJKV1QiLCJhbGci.payload.sig for auth',
    ];

    $redacted = $this->redactor->redact($payload);

    expect($redacted['note'])->not->toContain('eyJ0eXAiOiJKV1QiLCJhbGci');
    expect($redacted['note'])->toContain(Redactor::REDACTED);
});

it('leaves non-sensitive keys and values untouched', function () {
    $payload = [
        'id' => '123',
        'type' => 'card',
        'attributes' => [
            'name' => 'Charizard',
            'year' => 1999,
        ],
    ];

    $redacted = $this->redactor->redact($payload);

    expect($redacted)->toBe($payload);
});

it('masks bearer tokens in a raw body string', function () {
    $body = 'token=Bearer abc123def456 granted';

    $masked = $this->redactor->redactBody($body);

    expect($masked)->not->toContain('abc123def456');
    expect($masked)->toContain(Redactor::REDACTED);
});

it('masks key=value credential pairs in a non-JSON form-urlencoded body', function () {
    $body = 'grant_type=client_credentials&client_secret=super-secret&access_token=tok-123';

    $masked = $this->redactor->redactBody($body);

    expect($masked)->not->toContain('super-secret');
    expect($masked)->not->toContain('tok-123');
    expect($masked)->toContain('client_secret='.Redactor::REDACTED);
    expect($masked)->toContain('access_token='.Redactor::REDACTED);
    // Non-credential params survive so the body stays useful for debugging.
    expect($masked)->toContain('grant_type=client_credentials');
});

it('masks key=value credential pairs case-insensitively in free text', function () {
    $body = 'auth failed: CLIENT_SECRET=leak Password=hunter2 still bad';

    $masked = $this->redactor->redactBody($body);

    expect($masked)->not->toContain('leak');
    expect($masked)->not->toContain('hunter2');
    expect($masked)->toContain(Redactor::REDACTED);
});

it('returns null and empty bodies unchanged', function () {
    expect($this->redactor->redactBody(null))->toBeNull();
    expect($this->redactor->redactBody(''))->toBe('');
});

it('leaves a body with no credential material unchanged', function () {
    $body = '{"message":"Card not found"}';

    expect($this->redactor->redactBody($body))->toBe($body);
});
