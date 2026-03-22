<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when network-related errors occur (connection timeouts, DNS issues, etc.)
 */
class NetworkException extends TradingCardApiException
{
    /**
     * Constructor
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Exception|null  $previous  The previous exception
     * @param  string|null  $apiErrorCode  The API error code
     * @param  array  $apiErrors  The API errors array
     * @param  array  $context  Additional context for debugging
     */
    public function __construct(
        string $message = 'Network error occurred',
        int $code = 0,
        ?\Exception $previous = null,
        ?string $apiErrorCode = null,
        array $apiErrors = [],
        array $context = []
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            $apiErrorCode,
            $apiErrors,
            null, // No HTTP status for network errors
            $context
        );
    }

    /**
     * Create exception for connection timeout
     *
     * @param  array<string, mixed>  $context
     */
    public static function connectionTimeout(float $timeout, array $context = []): self
    {
        return new self(
            "Connection timed out after {$timeout} seconds",
            0,
            null,
            'connection_timeout',
            [[
                'title' => 'Connection Timeout',
                'detail' => "Unable to establish connection within {$timeout} seconds",
            ]],
            array_merge($context, ['timeout' => $timeout])
        );
    }

    /**
     * Create exception for request timeout
     *
     * @param  array<string, mixed>  $context
     */
    public static function requestTimeout(float $timeout, array $context = []): self
    {
        return new self(
            "Request timed out after {$timeout} seconds",
            0,
            null,
            'request_timeout',
            [[
                'title' => 'Request Timeout',
                'detail' => "Request took longer than {$timeout} seconds to complete",
            ]],
            array_merge($context, ['timeout' => $timeout])
        );
    }

    /**
     * Create exception for DNS resolution failure
     *
     * @param  array<string, mixed>  $context
     */
    public static function dnsResolutionFailed(string $hostname, array $context = []): self
    {
        return new self(
            "Failed to resolve hostname: {$hostname}",
            0,
            null,
            'dns_resolution_failed',
            [[
                'title' => 'DNS Resolution Failed',
                'detail' => "Unable to resolve the hostname '{$hostname}'",
            ]],
            array_merge($context, ['hostname' => $hostname])
        );
    }

    /**
     * Create exception for connection refused
     *
     * @param  array<string, mixed>  $context
     */
    public static function connectionRefused(string $host, int $port, array $context = []): self
    {
        return new self(
            "Connection refused to {$host}:{$port}",
            0,
            null,
            'connection_refused',
            [[
                'title' => 'Connection Refused',
                'detail' => "The connection to {$host}:{$port} was refused",
            ]],
            array_merge($context, ['host' => $host, 'port' => $port])
        );
    }

    /**
     * Create exception for SSL/TLS certificate errors
     *
     * @param  array<string, mixed>  $context
     */
    public static function sslError(string $message, array $context = []): self
    {
        return new self(
            "SSL/TLS error: {$message}",
            0,
            null,
            'ssl_error',
            [[
                'title' => 'SSL/TLS Error',
                'detail' => $message,
            ]],
            $context
        );
    }

    /**
     * Create exception from Guzzle ConnectException
     *
     * @param  array<string, mixed>  $context
     */
    public static function fromGuzzleConnectException(\Exception $exception, array $context = []): self
    {
        $message = $exception->getMessage();

        // Detect specific network error types
        if (strpos($message, 'Connection timed out') !== false) {
            preg_match('/after ([\d.]+) seconds/', $message, $matches);
            $timeout = isset($matches[1]) ? (float) $matches[1] : 0;

            return self::connectionTimeout($timeout, $context);
        }

        if (strpos($message, 'Connection refused') !== false) {
            // Extract host and port if possible
            preg_match('/to ([^:]+):?(\d+)?/', $message, $matches);
            $host = $matches[1] ?? 'unknown';
            $port = isset($matches[2]) ? (int) $matches[2] : 80;

            return self::connectionRefused($host, $port, $context);
        }

        if (strpos($message, 'Could not resolve host') !== false) {
            preg_match('/host[\s:]+([^\s,]+)/', $message, $matches);
            $hostname = $matches[1] ?? 'unknown';

            return self::dnsResolutionFailed($hostname, $context);
        }

        if (strpos($message, 'SSL') !== false || strpos($message, 'TLS') !== false) {
            return self::sslError($message, $context);
        }

        // Generic network exception
        return new self(
            "Network error: {$message}",
            0,
            $exception,
            'network_error',
            [[
                'title' => 'Network Error',
                'detail' => $message,
            ]],
            $context
        );
    }
}
