<?php

use CardTechie\TradingCardApiSdk\Exceptions\NetworkException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

it('creates network exception with default values', function () {
    $exception = new NetworkException;

    expect($exception->getMessage())->toBe('Network error occurred');
    expect($exception->getHttpStatusCode())->toBeNull(); // Network errors don't have HTTP status
});

it('creates connection timeout exception', function () {
    $exception = NetworkException::connectionTimeout(30.0, ['endpoint' => '/api/test']);

    expect($exception->getMessage())->toBe('Connection timed out after 30 seconds');
    expect($exception->getApiErrorCode())->toBe('connection_timeout');
    expect($exception->getContext())->toBe(['endpoint' => '/api/test', 'timeout' => 30.0]);
});

it('creates request timeout exception', function () {
    $exception = NetworkException::requestTimeout(60.0, ['request_id' => '123']);

    expect($exception->getMessage())->toBe('Request timed out after 60 seconds');
    expect($exception->getApiErrorCode())->toBe('request_timeout');
    expect($exception->getContext())->toBe(['request_id' => '123', 'timeout' => 60.0]);
});

it('creates DNS resolution failed exception', function () {
    $exception = NetworkException::dnsResolutionFailed('api.example.com', ['attempt' => 1]);

    expect($exception->getMessage())->toBe('Failed to resolve hostname: api.example.com');
    expect($exception->getApiErrorCode())->toBe('dns_resolution_failed');
    expect($exception->getContext())->toBe(['attempt' => 1, 'hostname' => 'api.example.com']);
});

it('creates connection refused exception', function () {
    $exception = NetworkException::connectionRefused('api.example.com', 443, ['attempt' => 2]);

    expect($exception->getMessage())->toBe('Connection refused to api.example.com:443');
    expect($exception->getApiErrorCode())->toBe('connection_refused');
    expect($exception->getContext())->toBe(['attempt' => 2, 'host' => 'api.example.com', 'port' => 443]);
});

it('creates SSL error exception', function () {
    $exception = NetworkException::sslError('Certificate verification failed', ['cert_chain' => 'info']);

    expect($exception->getMessage())->toBe('SSL/TLS error: Certificate verification failed');
    expect($exception->getApiErrorCode())->toBe('ssl_error');
    expect($exception->getContext())->toBe(['cert_chain' => 'info']);
});

it('creates network exception from Guzzle connect exception with timeout', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new ConnectException('Connection timed out after 30.5 seconds', $request);

    $exception = NetworkException::fromGuzzleConnectException($guzzleException, ['test' => true]);

    expect($exception->getMessage())->toBe('Connection timed out after 30.5 seconds');
    expect($exception->getApiErrorCode())->toBe('connection_timeout');
    expect($exception->getContext())->toBe(['test' => true, 'timeout' => 30.5]);
});

it('creates network exception from Guzzle connect exception with connection refused', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new ConnectException('Connection refused to api.example.com:443', $request);

    $exception = NetworkException::fromGuzzleConnectException($guzzleException);

    expect($exception->getMessage())->toBe('Connection refused to api.example.com:443');
    expect($exception->getApiErrorCode())->toBe('connection_refused');
    expect($exception->getContext())->toBe(['host' => 'api.example.com', 'port' => 443]);
});

it('creates network exception from Guzzle connect exception with DNS failure', function () {
    $request = new Request('GET', 'https://nonexistent.example.com');
    $guzzleException = new ConnectException('Could not resolve host: nonexistent.example.com', $request);

    $exception = NetworkException::fromGuzzleConnectException($guzzleException);

    expect($exception->getMessage())->toBe('Failed to resolve hostname: nonexistent.example.com');
    expect($exception->getApiErrorCode())->toBe('dns_resolution_failed');
    expect($exception->getContext())->toBe(['hostname' => 'nonexistent.example.com']);
});

it('creates network exception from Guzzle connect exception with SSL error', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new ConnectException('SSL certificate problem: unable to get local issuer certificate', $request);

    $exception = NetworkException::fromGuzzleConnectException($guzzleException);

    expect($exception->getMessage())->toBe('SSL/TLS error: SSL certificate problem: unable to get local issuer certificate');
    expect($exception->getApiErrorCode())->toBe('ssl_error');
});

it('creates generic network exception from unknown Guzzle connect exception', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new ConnectException('Unknown network error occurred', $request);

    $exception = NetworkException::fromGuzzleConnectException($guzzleException, ['context' => 'test']);

    expect($exception->getMessage())->toBe('Network error: Unknown network error occurred');
    expect($exception->getApiErrorCode())->toBe('network_error');
    expect($exception->getContext())->toBe(['context' => 'test']);
    expect($exception->getPrevious())->toBe($guzzleException);
});
