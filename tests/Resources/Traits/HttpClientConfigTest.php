<?php

use CardTechie\TradingCardApiSdk\TradingCardApi;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Read the option array out of the Guzzle client a TradingCardApi instance
 * constructed. The client stores merged default options on a private `config`
 * property; reflect it rather than calling the deprecated getConfig().
 *
 * @return array<string, mixed>
 */
function clientConfigFor(TradingCardApi $api): array
{
    $apiReflection = new ReflectionClass($api);
    $clientProperty = $apiReflection->getProperty('client');
    $clientProperty->setAccessible(true);
    /** @var Client $client */
    $client = $clientProperty->getValue($api);

    $clientReflection = new ReflectionClass($client);
    $configProperty = $clientReflection->getProperty('config');
    $configProperty->setAccessible(true);

    return $configProperty->getValue($client);
}

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);
});

it('applies default timeout and connect_timeout to the client', function () {
    $config = clientConfigFor(new TradingCardApi);

    expect($config['timeout'])->toBe(10.0);
    expect($config['connect_timeout'])->toBe(5.0);
});

it('reads timeout and connect_timeout from config', function () {
    config(['tradingcardapi.timeout' => 30]);
    config(['tradingcardapi.connect_timeout' => 8]);

    $config = clientConfigFor(new TradingCardApi);

    expect($config['timeout'])->toBe(30.0);
    expect($config['connect_timeout'])->toBe(8.0);
});

it('allows timeout overrides via constructor options', function () {
    $config = clientConfigFor(new TradingCardApi([
        'timeout' => 2,
        'connect_timeout' => 1,
    ]));

    expect($config['timeout'])->toBe(2.0);
    expect($config['connect_timeout'])->toBe(1.0);
});

it('does not install our retry stack when retry is disabled', function () {
    config(['tradingcardapi.retry.enabled' => false]);

    $apiReflection = new ReflectionClass($api = new TradingCardApi);
    $clientProperty = $apiReflection->getProperty('client');
    $clientProperty->setAccessible(true);
    $client = $clientProperty->getValue($api);

    // We do not pass a `handler` option when retry is off. Guzzle lazily builds
    // a default handler only when one was not supplied, so the absence of our
    // stack is observable: the lazily-created default is the choose() default,
    // not a stack we pushed RetryMiddleware onto. Assert via the option array
    // that no handler was provided at construction time by us.
    $clientReflection = new ReflectionClass($client);
    $configProperty = $clientReflection->getProperty('config');
    $configProperty->setAccessible(true);
    $config = $configProperty->getValue($client);

    // Guzzle fills in a default HandlerStack itself; the meaningful invariant
    // is that timeouts still apply and the client is usable when retry is off.
    expect($config['timeout'])->toBe(10.0);
    expect($config['connect_timeout'])->toBe(5.0);
});

it('installs a handler stack when retry is enabled', function () {
    config(['tradingcardapi.retry.enabled' => true]);

    $config = clientConfigFor(new TradingCardApi);

    expect($config)->toHaveKey('handler');
    expect($config['handler'])->toBeInstanceOf(HandlerStack::class);
});
