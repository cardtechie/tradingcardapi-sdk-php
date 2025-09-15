<?php

namespace CardTechie\TradingCardApiSdk\Resources;

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
}
