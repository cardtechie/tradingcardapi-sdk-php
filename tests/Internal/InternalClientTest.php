<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Internal\InternalClient;
use CardTechie\TradingCardApiSdk\Internal\Resources\AuditLog;
use CardTechie\TradingCardApiSdk\Internal\Resources\Workflow;
use CardTechie\TradingCardApiSdk\TradingCardApi;

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);
});

it('TradingCardApi::internal() returns an InternalClient', function () {
    $api = new TradingCardApi;
    $internal = $api->internal();

    expect($internal)->toBeInstanceOf(InternalClient::class);
});

it('TradingCardApi::internal()->workflow() returns an Internal Workflow', function () {
    $api = new TradingCardApi;
    $workflow = $api->internal()->workflow();

    expect($workflow)->toBeInstanceOf(Workflow::class);
});

it('TradingCardApi::internal()->auditLog() returns an Internal AuditLog', function () {
    $api = new TradingCardApi;
    $auditLog = $api->internal()->auditLog();

    expect($auditLog)->toBeInstanceOf(AuditLog::class);
});

it('TradingCardApi no longer exposes workflow() at the top level', function () {
    $api = new TradingCardApi;

    expect(method_exists($api, 'workflow'))->toBeFalse();
});

it('TradingCardApi no longer exposes auditLog() at the top level', function () {
    $api = new TradingCardApi;

    expect(method_exists($api, 'auditLog'))->toBeFalse();
});

it('InternalClient passes auth info to workflow resource', function () {
    $api = TradingCardApi::withPersonalAccessToken('test-pat-token');
    $workflow = $api->internal()->workflow();

    $reflection = new ReflectionClass($workflow);
    $authTypeProperty = $reflection->getProperty('authType');
    $authTypeProperty->setAccessible(true);

    expect($authTypeProperty->getValue($workflow))->toBe('pat');
});

it('InternalClient passes auth info to auditLog resource', function () {
    $api = TradingCardApi::withClientCredentials('client-id', 'client-secret');
    $auditLog = $api->internal()->auditLog();

    $reflection = new ReflectionClass($auditLog);
    $authTypeProperty = $reflection->getProperty('authType');
    $authTypeProperty->setAccessible(true);

    expect($authTypeProperty->getValue($auditLog))->toBe('oauth2');
});
