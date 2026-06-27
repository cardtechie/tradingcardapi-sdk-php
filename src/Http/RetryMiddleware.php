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
 *
 * By default only idempotent HTTP methods (GET, HEAD, PUT, DELETE, OPTIONS,
 * TRACE) are retried. Retrying a non-idempotent request (POST, PATCH) after
 * the server may have already partially processed it can duplicate side
 * effects, so non-idempotent methods are never retried unless the integrator
 * opts in via the `retry_non_idempotent` config flag.
 */
class RetryMiddleware
{
    /**
     * HTTP methods that are safe to retry: a retried request has the same
     * effect on the server as a single request, so replaying it after a
     * transient failure cannot duplicate side effects.
     *
     * @var array<int, string>
     */
    private const IDEMPOTENT_METHODS = ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'TRACE'];

    /**
     * Build the retry middleware from the resolved `retry` config block.
     *
     * @param  array<string, mixed>  $config  Keys: max_attempts (int), base_delay (int, ms), retry_non_idempotent (bool)
     */
    public static function make(array $config): callable
    {
        $maxAttempts = (int) ($config['max_attempts'] ?? 3);
        $baseDelay = (int) ($config['base_delay'] ?? 1000);
        // Normalize with FILTER_VALIDATE_BOOLEAN rather than a plain (bool)
        // cast: a direct config array could carry a string like 'false', which
        // (bool) would treat as true and silently enable non-idempotent retries.
        $retryNonIdempotent = filter_var($config['retry_non_idempotent'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return Middleware::retry(
            self::decider($maxAttempts, $retryNonIdempotent),
            self::delay($baseDelay),
        );
    }

    /**
     * Whether the request uses an idempotent HTTP method (case-insensitive),
     * i.e. one that is safe to replay after a transient failure.
     */
    private static function isIdempotent(RequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), self::IDEMPOTENT_METHODS, true);
    }

    /**
     * The retry decider: retry while we have attempts left AND the failure is
     * transient (a connection error, a 429, or a 5xx response).
     *
     * Non-idempotent requests (e.g. POST, PATCH) are never retried unless
     * `$retryNonIdempotent` is true, because replaying them after a partially
     * processed request can duplicate side effects. This gate is applied
     * before the transient-failure branches, so it covers every failure class.
     *
     * @return callable(int, RequestInterface, ?ResponseInterface, ?\Throwable): bool
     */
    private static function decider(int $maxAttempts, bool $retryNonIdempotent): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Throwable $exception = null
        ) use ($maxAttempts, $retryNonIdempotent): bool {
            // `$retries` is the count of retries already performed (0 on the
            // first decision). Stop once we have used our budget.
            if ($retries >= $maxAttempts) {
                return false;
            }

            // Never retry a non-idempotent request unless explicitly opted in.
            // Checked before the transient-failure branches so it applies to
            // connection errors, 429s, and 5xx responses alike.
            if (! $retryNonIdempotent && ! self::isIdempotent($request)) {
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
