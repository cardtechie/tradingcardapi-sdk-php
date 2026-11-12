<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\DTOs\Stats\CountsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\EntityCount;
use CardTechie\TradingCardApiSdk\DTOs\Stats\GrowthResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\SnapshotsResponse;
use CardTechie\TradingCardApiSdk\DTOs\Stats\StatsResponse;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;

/**
 * The /v1/stats/* endpoints.
 *
 * All four methods return figures describing what the calling token can read,
 * not what exists in the catalog. Since cardtechie/tradingcardapi-api#2435 a
 * token without `internal`, `read:all-status` or `read:draft` — which includes
 * this SDK's default `read:published` scope — receives a published-only view,
 * with no key in the payload signalling that it did. The response shape is
 * identical under both postures.
 *
 * @see EntityCount for the full rule
 * @see https://github.com/cardtechie/tradingcardapi-api/issues/2435
 */
class Stats
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the time-series stats for a single model type.
     *
     * For `sets` and `cards` the series is row-filtered by the calling token's
     * status posture, so a `read:published` token sees a published-only series.
     * Taxonomy types (players, teams, attributes, brands, genres, manufacturers,
     * years) are ungated.
     *
     * @param  string  $type  The model type, plural (e.g. cards, sets, players)
     */
    public function get(string $type): StatsResponse
    {
        $url = sprintf('/v1/stats/%s', $type);
        $response = $this->makeRequest($url);

        return StatsResponse::fromResponse($response);
    }

    /**
     * Get current counts for all entity types.
     *
     * The returned figures are relative to the calling token's status posture:
     * on the default `read:published` scope each row arrives with
     * `total == published` and `draft == 0`.
     *
     * Rows are keyed by the API's singular entity_type (`set`, `card`,
     * `player`, `team`).
     */
    public function getCounts(): CountsResponse
    {
        $response = $this->makeRequest('/v1/stats/counts');

        return CountsResponse::fromResponse($response);
    }

    /**
     * Get historical snapshots with optional filters.
     *
     * Each row carries the same posture-relative figures as getCounts().
     *
     * @param  array<string, string>  $filters  Supported filters: entity_type, from, to.
     *                                          entity_type takes the singular form.
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
     * The metric keys are posture-independent but the series behind them is not:
     * a token without draft visibility gets growth computed off the published
     * series, and zeros when the window carries no published data.
     *
     * @param  string  $period  Supported periods: 7d, 30d, 90d, month, week
     */
    public function getGrowth(string $period = '7d'): GrowthResponse
    {
        $url = sprintf('/v1/stats/growth?%s', http_build_query(['period' => $period]));
        $response = $this->makeRequest($url);

        return GrowthResponse::fromResponse($response);
    }
}
