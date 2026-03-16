<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;

class Workflow
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the actionable sets for the workflow dashboard.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function actionableSets(array $params = []): object
    {
        $url = '/v1/workflow/actionable-sets';
        if (! empty($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $this->makeRequest($url, 'GET');
    }
}
