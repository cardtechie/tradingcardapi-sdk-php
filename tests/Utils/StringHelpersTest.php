<?php

use CardTechie\TradingCardApiSdk\Utils\StringHelpers;

it('isValidUuid returns true for valid UUIDs', function () {
    $validUuids = [
        '550e8400-e29b-41d4-a716-446655440000',
        '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        '12345678-1234-1234-1234-123456789012',
    ];

    foreach ($validUuids as $uuid) {
        expect(StringHelpers::isValidUuid($uuid))->toBeTrue("Failed for UUID: {$uuid}");
    }
});

it('isValidUuid returns false for invalid UUIDs', function () {
    $invalidUuids = [
        null,
        '',
        'not-a-uuid',
        '550e8400-e29b-41d4-a716',  // Too short
        '550e8400-e29b-41d4-a716-446655440000-extra',  // Too long
        '550e8400-e29b-41d4-a716-44665544000g',  // Invalid character 'g'
        '550e8400-e29b-41d4-a716_446655440000',  // Wrong separator
        '550e8400e29b41d4a716446655440000',  // No separators
        '550e8400-e29b-41d4-a716-446655440000-',  // Trailing dash
        '-550e8400-e29b-41d4-a716-446655440000',  // Leading dash
    ];

    foreach ($invalidUuids as $uuid) {
        expect(StringHelpers::isValidUuid($uuid))->toBeFalse('Failed for invalid UUID: '.($uuid ?? 'null'));
    }
});

it('normalizeName normalizes names correctly', function () {
    expect(StringHelpers::normalizeName('  John Doe  '))->toBe('john doe');
    expect(StringHelpers::normalizeName('JOHN DOE'))->toBe('john doe');
    expect(StringHelpers::normalizeName('John Doe'))->toBe('john doe');
    expect(StringHelpers::normalizeName(''))->toBe('');
    expect(StringHelpers::normalizeName(null))->toBe('');
    expect(StringHelpers::normalizeName('   '))->toBe('');
});

it('namesMatch matches names correctly', function () {
    expect(StringHelpers::namesMatch('John Doe', 'john doe'))->toBeTrue();
    expect(StringHelpers::namesMatch('  John Doe  ', 'JOHN DOE'))->toBeTrue();
    expect(StringHelpers::namesMatch('John Doe', 'Jane Doe'))->toBeFalse();
    expect(StringHelpers::namesMatch(null, null))->toBeTrue();
    expect(StringHelpers::namesMatch('', ''))->toBeTrue();
    expect(StringHelpers::namesMatch('John', null))->toBeFalse();
    expect(StringHelpers::namesMatch(null, 'John'))->toBeFalse();
});
