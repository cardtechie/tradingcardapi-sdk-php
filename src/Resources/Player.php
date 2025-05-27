<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Player as PlayerModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 * Class Player
 */
class Player
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
     * Retrieve a list of players
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getList(array $params = []): Collection
    {
        $query = http_build_query($params);
        $url = sprintf('/players?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }

    /**
     * Create a player
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes): PlayerModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'players',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        $response = $this->makeRequest('/players', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
