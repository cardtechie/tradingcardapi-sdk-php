<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\DTOs\Stats\CountsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\SnapshotsResponse;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;

class Stats
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get(string $type): \stdClass
    {
        $url = sprintf('/v1/stats/%s', $type);
        $response = $this->makeRequest($url);

        // Stats response doesn't have an ID field, so we handle it directly
        return $response->data->attributes;
    }

    /**
     * Get current counts for all entity types.
     */
    public function getCounts(): CountsResponse
    {
        $response = $this->makeRequest('/v1/stats/counts');

        return CountsResponse::fromResponse($response);
    }

    /**
     * Get historical snapshots with optional filters.
     *
     * @param  array<string, string>  $filters  Supported filters: entity_type, from, to
     */
    public function getSnapshots(array $filters = []): SnapshotsResponse
    {
        $url = '/v1/stats/snapshots';
        if (count($filters) > 0) {
            $url .= '?'.http_build_query($filters);
        }
        $response = $this->makeRequest($url);

        return SnapshotsResponse::fromResponse($response);
    }

    /**
     * Get growth metrics for a period.
     *
     * @param  string  $period  Supported periods: 7d, 30d, 90d, month, week
     */
    public function getGrowth(string $period = '7d'): GrowthResponse
    {
        $url = sprintf('/v1/stats/growth?period=%s', $period);
        $response = $this->makeRequest($url);

        return GrowthResponse::fromResponse($response);
    }
}
