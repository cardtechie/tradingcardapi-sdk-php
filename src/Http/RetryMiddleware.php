<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds a Guzzle retry middleware with exponential backoff for transient
 * failures (HTTP 429, 5xx responses, and connection errors).
 *
 * When a response carries a numeric `Retry-After` header, that value is
 * honored in preference to the computed backoff delay. This lets the SDK
 * respect server-directed rate limiting instead of every integrator having
 * to hand-roll 429 handling.
 */
class RetryMiddleware
{
    /**
     * Build the retry middleware from the resolved `retry` config block.
     *
     * @param  array<string, mixed>  $config  Keys: max_attempts (int), base_delay (int, ms)
     */
    public static function make(array $config): callable
    {
        $maxAttempts = (int) ($config['max_attempts'] ?? 3);
        $baseDelay = (int) ($config['base_delay'] ?? 1000);

        return Middleware::retry(
            self::decider($maxAttempts),
            self::delay($baseDelay),
        );
    }

    /**
     * The retry decider: retry while we have attempts left AND the failure is
     * transient (a connection error, a 429, or a 5xx response).
     *
     * @return callable(int, RequestInterface, ?ResponseInterface, ?\Throwable): bool
     */
    private static function decider(int $maxAttempts): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Throwable $exception = null
        ) use ($maxAttempts): bool {
            // `$retries` is the count of retries already performed (0 on the
            // first decision). Stop once we have used our budget.
            if ($retries >= $maxAttempts) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response !== null) {
                $status = $response->getStatusCode();

                return $status === 429 || $status >= 500;
            }

            return false;
        };
    }

    /**
     * The delay closure: exponential backoff in milliseconds, overridden by a
     * numeric `Retry-After` header when present on the response.
     *
     * Guzzle's retry middleware expects the delay in milliseconds.
     *
     * @return callable(int, ?ResponseInterface): int
     */
    private static function delay(int $baseDelay): callable
    {
        return function (int $retries, ?ResponseInterface $response = null) use ($baseDelay): int {
            if ($response !== null && $response->hasHeader('Retry-After')) {
                $retryAfter = $response->getHeaderLine('Retry-After');

                // Only honor a numeric (seconds) Retry-After; an HTTP-date form
                // is not parsed here and falls through to exponential backoff.
                if (is_numeric($retryAfter)) {
                    return (int) ((float) $retryAfter * 1000);
                }
            }

            // `$retries` is the number of retries already done; the upcoming
            // attempt is attempt = $retries + 1, so the exponent is $retries.
            return (int) ($baseDelay * (2 ** $retries));
        };
    }
}
