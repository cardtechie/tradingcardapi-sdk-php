<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Schemas\AuditLogSchema;
use Illuminate\Support\Facades\Validator;

it('provides validation rules for single audit log response', function () {
    $schema = new AuditLogSchema;
    $rules = $schema->getRules();

    // Should have base JSON API rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    // Should have audit-log-specific rules
    expect($rules)->toHaveKey('data.attributes.auditable_type');
    expect($rules)->toHaveKey('data.attributes.auditable_id');
    expect($rules)->toHaveKey('data.attributes.event_type');
    expect($rules)->toHaveKey('data.attributes.user_id');
    expect($rules)->toHaveKey('data.attributes.ip_address');
    expect($rules)->toHaveKey('data.attributes.old_values');
    expect($rules)->toHaveKey('data.attributes.new_values');
    expect($rules)->toHaveKey('data.attributes.created_at');
    expect($rules)->toHaveKey('data.attributes.updated_at');

    // Should enforce audit-log type
    expect($rules['data.type'])->toContain('in:audit-logs,audit-log');
});

it('validates valid audit log response successfully', function () {
    $schema = new AuditLogSchema;

    $validAuditLogData = [
        'data' => [
            'id' => '42',
            'type' => 'audit-logs',
            'attributes' => [
                'auditable_type' => 'Card',
                'auditable_id' => '123',
                'event_type' => 'updated',
                'user_id' => '7',
                'ip_address' => '127.0.0.1',
                'old_values' => '{"name":"Old Name"}',
                'new_values' => '{"name":"New Name"}',
                'created_at' => '2026-01-01T00:00:00Z',
                'updated_at' => '2026-01-01T00:00:00Z',
            ],
        ],
        'meta' => [
            'total' => 1,
        ],
    ];

    $validator = Validator::make($validAuditLogData, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid audit log type', function () {
    $schema = new AuditLogSchema;

    $invalidData = [
        'data' => [
            'id' => '42',
            'type' => 'cards', // Wrong type
            'attributes' => [
                'event_type' => 'created',
            ],
        ],
    ];

    $validator = Validator::make($invalidData, $schema->getRules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('data.type'))->toBeTrue();
});

it('validates audit log with nullable fields', function () {
    $schema = new AuditLogSchema;

    $auditLogWithNulls = [
        'data' => [
            'id' => '42',
            'type' => 'audit-log',
            'attributes' => [
                'auditable_type' => null,
                'auditable_id' => null,
                'event_type' => null,
                'user_id' => null,
                'ip_address' => null,
                'old_values' => null,
                'new_values' => null,
                'created_at' => null,
                'updated_at' => null,
            ],
        ],
    ];

    $validator = Validator::make($auditLogWithNulls, $schema->getRules());
    expect($validator->passes())->toBeTrue();
});

it('provides validation rules for audit log collection response', function () {
    $schema = new AuditLogSchema;
    $rules = $schema->getCollectionRules();

    // Should have collection-specific rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    // Should have audit-log-specific collection rules
    expect($rules)->toHaveKey('data.*.attributes.auditable_type');
    expect($rules)->toHaveKey('data.*.attributes.event_type');
    expect($rules['data.*.type'])->toContain('in:audit-logs,audit-log');
});

it('validates audit log collection response successfully', function () {
    $schema = new AuditLogSchema;

    $auditLogCollection = [
        'data' => [
            [
                'id' => '1',
                'type' => 'audit-logs',
                'attributes' => [
                    'auditable_type' => 'Card',
                    'auditable_id' => '10',
                    'event_type' => 'created',
                    'user_id' => '3',
                    'ip_address' => '10.0.0.1',
                    'old_values' => null,
                    'new_values' => '{"name":"New Card"}',
                    'created_at' => '2026-01-01T00:00:00Z',
                    'updated_at' => '2026-01-01T00:00:00Z',
                ],
            ],
            [
                'id' => '2',
                'type' => 'audit-logs',
                'attributes' => [
                    'auditable_type' => 'Set',
                    'auditable_id' => '20',
                    'event_type' => 'deleted',
                    'user_id' => '5',
                    'ip_address' => '10.0.0.2',
                    'old_values' => '{"name":"Old Set"}',
                    'new_values' => null,
                    'created_at' => '2026-01-02T00:00:00Z',
                    'updated_at' => '2026-01-02T00:00:00Z',
                ],
            ],
        ],
        'meta' => [
            'total' => 2,
            'per_page' => 50,
            'current_page' => 1,
        ],
    ];

    $validator = Validator::make($auditLogCollection, $schema->getCollectionRules());
    expect($validator->passes())->toBeTrue();
});
