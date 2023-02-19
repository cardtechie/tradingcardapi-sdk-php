<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Playerteam as PlayerteamModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 * Class Playerteam
 */
class Playerteam
{
    use ApiRequest;

    /**
     * Playerteam constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve a playerteam by player and/or team id
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getList(array $params = []): Collection
    {
        $query = http_build_query($params);
        $url = sprintf('/playerteams?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }

    /**
     * Create a playerteam
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes): PlayerteamModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'playerteams',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        $response = $this->makeRequest('/playerteams', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
