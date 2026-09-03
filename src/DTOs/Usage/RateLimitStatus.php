<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Usage;

/**
 * The caller's rate-limit window as reported by the `X-RateLimit-*` response
 * headers that ride along on every API response.
 *
 * This is the passive counterpart to {@see UsageResponse}, which is the
 * document returned by an explicit `GET /v1/user/usage` poll. The two are
 * deliberately distinct types because their reset fields do not share a
 * representation: `UsageResponse::$resetsAt` is an ISO-8601 **string** (the
 * endpoint's `resets_at` attribute), whereas `$resetAt` here is an absolute
 * Unix **timestamp** (the header value the API's throttle middleware emits).
 * Reusing one type for both would force a lossy conversion at the boundary.
 *
 * The accessors mirror the shape `RateLimitException` already exposes for the
 * 429 path (`getRateLimitResetDateTime()`, `getSecondsUntilReset()`) so the
 * passive and throwing paths read alike.
 */
class RateLimitStatus
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetAt,
    ) {}

    /**
     * Build a status from a response header map, or `null` when the header
     * trio is not fully present.
     *
     * The API's throttle middleware does not guarantee the headers: its
     * `getHeaders()` defers to the framework parent, which returns an empty
     * array under the stacked-limiter precedence guard. A partial trio is
     * therefore treated as "no reading" rather than as zeroes, so callers
     * never mistake an absent header for an exhausted quota.
     *
     * Header lookup is case-insensitive: PSR-7 header names are
     * case-insensitive and Guzzle preserves whatever casing came off the wire,
     * so an exact-key lookup would be fragile.
     *
     * @param  array<string, mixed>  $headers  Header map; values may be scalars or
     *                                         PSR-7 style arrays of values
     */
    public static function fromHeaders(array $headers): ?self
    {
        $limit = self::headerInt($headers, 'x-ratelimit-limit');
        $remaining = self::headerInt($headers, 'x-ratelimit-remaining');
        $resetAt = self::headerInt($headers, 'x-ratelimit-reset');

        if ($limit === null || $remaining === null || $resetAt === null) {
            return null;
        }

        return new self(
            limit: $limit,
            remaining: $remaining,
            resetAt: $resetAt,
        );
    }

    /**
     * Requests already consumed in the current window.
     *
     * Clamped at zero so a `remaining` larger than `limit` (which can happen
     * momentarily as the API rolls a window over) never yields a negative
     * count. Mirrors {@see UsageResponse::used()}.
     */
    public function used(): int
    {
        return max(0, $this->limit - $this->remaining);
    }

    /**
     * The moment the current window resets.
     */
    public function resetAtDateTime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable)->setTimestamp($this->resetAt);
    }

    /**
     * Seconds remaining until the window resets, clamped at zero for a
     * timestamp that has already passed. Matches the semantics of
     * {@see \CardTechie\TradingCardApiSdk\Exceptions\RateLimitException::getSecondsUntilReset()}.
     */
    public function secondsUntilReset(): int
    {
        return max(0, $this->resetAt - time());
    }

    /**
     * Case-insensitively read one header and cast it to an int, or return
     * `null` when the header is absent or carries no usable value.
     *
     * @param  array<string, mixed>  $headers
     * @param  string  $needle  Lower-cased header name to find
     */
    private static function headerInt(array $headers, string $needle): ?int
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== $needle) {
                continue;
            }

            // PSR-7 exposes each header as a list of values; take the first.
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            if ($value === null || $value === '' || is_array($value)) {
                return null;
            }

            return (int) $value;
        }

        return null;
    }
}
