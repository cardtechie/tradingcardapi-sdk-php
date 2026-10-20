<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\DTOs\Usage\UsageResponse;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;

class Usage
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the calling credential's own rate-limit window.
     *
     * The response is scoped to the credential used for the request — there is
     * no way to read another key's window — and the read itself is not metered
     * against the bucket it reports on.
     *
     * Note: until cardtechie/tradingcardapi-api#2342 ships, this endpoint is
     * gated to interactive portal sessions, so an opaque `tc_` key receives a
     * 401 that the SDK surfaces as an `AuthenticationException`.
     */
    public function get(): UsageResponse
    {
        $response = $this->makeRequest('/v1/user/usage');

        return UsageResponse::fromResponse($response);
    }
}
