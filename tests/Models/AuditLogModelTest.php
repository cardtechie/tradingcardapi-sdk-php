<?php

use CardTechie\TradingCardApiSdk\Models\AuditLog;
use CardTechie\TradingCardApiSdk\Models\Model;

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
    ]);
});

it('can be instantiated without attributes', function () {
    $auditLog = new AuditLog;

    expect($auditLog)->toBeInstanceOf(AuditLog::class);
    expect($auditLog)->toBeInstanceOf(Model::class);
});

it('can be instantiated with attributes', function () {
    $attributes = [
        'id' => '42',
        'auditable_type' => 'Set',
        'auditable_id' => 'set-123',
        'event_type' => 'created',
        'created_at' => '2026-04-13T10:00:00Z',
    ];

    $auditLog = new AuditLog($attributes);

    expect($auditLog->id)->toBe('42');
    expect($auditLog->auditable_type)->toBe('Set');
    expect($auditLog->auditable_id)->toBe('set-123');
    expect($auditLog->event_type)->toBe('created');
    expect($auditLog->created_at)->toBe('2026-04-13T10:00:00Z');
});

it('returns null for non-existent attributes', function () {
    $auditLog = new AuditLog(['id' => '42']);

    expect($auditLog->nonExistentProperty)->toBeNull();
});

it('magic isset works correctly', function () {
    $auditLog = new AuditLog(['id' => '42', 'event_type' => 'created']);

    expect(isset($auditLog->id))->toBeTrue();
    expect(isset($auditLog->event_type))->toBeTrue();
    expect(isset($auditLog->nonExistentProperty))->toBeFalse();
});

it('handles null attribute values gracefully', function () {
    $auditLog = new AuditLog(['id' => '42', 'description' => null]);

    expect($auditLog->id)->toBe('42');
    expect($auditLog->description)->toBeNull();
});

it('converts to string correctly', function () {
    $auditLog = new AuditLog([
        'id' => '42',
        'event_type' => 'created',
        'auditable_type' => 'Set',
    ]);

    $json = (string) $auditLog;
    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKey('id', '42');
    expect($decoded)->toHaveKey('event_type', 'created');
    expect($decoded)->toHaveKey('auditable_type', 'Set');
});

it('can set and get relationships', function () {
    $auditLog = new AuditLog(['id' => '42']);

    $auditLog->setRelationships(['users' => [['id' => 'user-1']]]);

    expect($auditLog->getRelationships())->toHaveKey('users');
});
