<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Usage;

/**
 * The caller's current rate-limit window, as returned by `GET /v1/user/usage`.
 *
 * The API scopes this document to the credential that made the request, so the
 * values describe the caller's own bucket and nobody else's.
 */
class UsageResponse
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly string $resetsAt,
    ) {}

    public static function fromResponse(object $response): self
    {
        $attributes = $response->data->attributes ?? null;

        return new self(
            limit: $attributes->limit ?? 0,
            remaining: $attributes->remaining ?? 0,
            resetsAt: $attributes->resets_at ?? '',
        );
    }

    /**
     * Requests already consumed in the current window.
     *
     * Clamped at zero so a `remaining` larger than `limit` (which can happen
     * momentarily as the API rolls a window over) never yields a negative count.
     */
    public function used(): int
    {
        return max(0, $this->limit - $this->remaining);
    }
}
