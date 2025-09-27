<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Player as PlayerModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Class Player
 */
class Player
{
    use ApiRequest;

    /**
     * Player constructor.
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
        $url = sprintf('/v1/players?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }

    /**
     * Create a player
     *
     * @param array $attributes Player attributes
     * @param array $relationships Player relationships
     *
     * @return PlayerModel The created player
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): PlayerModel
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

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/players', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a player by ID
     *
     * @param string $id Player ID
     * @param array $params Additional parameters (e.g., include relationships)
     *
     * @return PlayerModel The player
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): PlayerModel
    {
        $url = sprintf('/v1/players/%s', $id);
        $response = $this->makeRequest($url, 'GET', ['query' => $params]);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * List players with pagination
     *
     * @param array $params Query parameters (limit, page, sort, filters, etc.)
     *
     * @return LengthAwarePaginator Paginated player results
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(array $params = []): LengthAwarePaginator
    {
        $defaultParams = [
            'limit' => 50,
            'page' => 1,
            'pageName' => 'page',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/players?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        $totalPages = $response->meta->pagination->total;
        $perPage = $response->meta->pagination->per_page;
        $page = $response->meta->pagination->current_page;
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $params['pageName'],
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    /**
     * Update a player
     *
     * @param string $id Player ID
     * @param array $attributes Player attributes to update
     * @param array $relationships Player relationships to update
     *
     * @return PlayerModel The updated player
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): PlayerModel
    {
        $url = sprintf('/v1/players/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'players',
                    'id' => $id,
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest($url, 'PUT', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Delete a player
     *
     * @param string $id Player ID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = sprintf('/v1/players/%s', $id);
        $this->makeRequest($url, 'DELETE');
    }

    /**
     * List deleted players
     *
     * @return LengthAwarePaginator Paginated deleted player results
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function listDeleted(): LengthAwarePaginator
    {
        $response = $this->makeRequest('/v1/players/deleted');

        $totalPages = $response->meta->pagination->total ?? count($response->data);
        $perPage = $response->meta->pagination->per_page ?? max(count($response->data), 1);
        $page = $response->meta->pagination->current_page ?? 1;
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    /**
     * Get a deleted player by ID
     *
     * @param string $id Player ID
     *
     * @return PlayerModel The deleted player
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleted(string $id): PlayerModel
    {
        $url = sprintf('/v1/players/%s/deleted', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
