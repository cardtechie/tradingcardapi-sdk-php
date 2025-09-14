<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Team as TeamModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 * Class Team
 */
class Team
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
     * Retrieve a list of teams
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getList(array $params = []): Collection
    {
        $query = http_build_query($params);
        $url = sprintf('/v1/teams?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }

    /**
     * Create a team
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes): TeamModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'teams',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        $response = $this->makeRequest('/v1/teams', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
