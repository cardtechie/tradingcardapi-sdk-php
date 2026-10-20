<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Services;

use CardTechie\TradingCardApiSdk\DTOs\Usage\RateLimitStatus;

/**
 * Holds the most recently observed rate-limit window.
 *
 * `TradingCardApi::createResource()` builds a **fresh** resource instance on
 * every accessor call, so `$api->card()->list()` discards the `Card` the moment
 * the call returns. Per-instance state on the `ApiRequest` trait alone would
 * therefore be unreadable through the facade. This holder is created once per
 * client and injected into every resource that client makes, so a read through
 * `TradingCardApi::rateLimit()` sees whatever the last request recorded.
 */
class RateLimitTracker
{
    /**
     * The most recent reading, or null if no response has carried the headers.
     */
    private ?RateLimitStatus $status = null;

    /**
     * Store a reading, replacing any previous one.
     */
    public function record(RateLimitStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Record from a response header map, **leaving any previous reading
     * untouched when the trio is absent or incomplete.**
     *
     * This leave-untouched rule is deliberate. The API's throttle middleware
     * can legitimately omit the headers (its precedence guard returns an empty
     * header set), and a response carrying no rate-limit headers tells the
     * caller nothing new about their quota. Clearing on such a response would
     * discard a perfectly good earlier reading in exchange for no information.
     *
     * @param  array<string, mixed>  $headers
     */
    public function recordFromHeaders(array $headers): void
    {
        $status = RateLimitStatus::fromHeaders($headers);

        if ($status !== null) {
            $this->status = $status;
        }
    }

    /**
     * The most recent reading, or null if none has been observed yet.
     */
    public function get(): ?RateLimitStatus
    {
        return $this->status;
    }
}
