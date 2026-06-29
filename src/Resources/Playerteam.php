<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Playerteam as PlayerteamModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

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
     * Retrieve a raw collection of playerteams by player and/or team id
     *
     * @param  array<string, mixed>  $params  Query parameters
     * @return Collection<int, mixed> The playerteams collection
     *
     * @throws InvalidArgumentException
     */
    public function all(array $params = []): Collection
    {
        $query = http_build_query($params);
        $url = sprintf('/v1/playerteams?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response) ?: '{}');
    }

    /**
     * Retrieve a playerteam by player and/or team id
     *
     * @deprecated use all()
     *
     * @param  array<string, mixed>  $params  Query parameters
     * @return Collection<int, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function getList(array $params = []): Collection
    {
        return $this->all($params);
    }

    /**
     * List playerteams with pagination
     *
     * @param  array<string, mixed>  $params  Query parameters (limit, page, sort, filters, etc.)
     * @return LengthAwarePaginator<int, mixed> Paginated playerteam results
     *
     * @throws InvalidArgumentException
     */
    public function list(array $params = []): LengthAwarePaginator
    {
        $defaultParams = [
            'limit' => 50,
            'page' => 1,
            'pageName' => 'page',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/playerteams?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        // Handle missing meta information gracefully
        $totalPages = isset($response->meta->pagination->total) ? $response->meta->pagination->total : count($response->data);
        $perPage = isset($response->meta->pagination->per_page) ? $response->meta->pagination->per_page : ($params['limit'] ?? 50);
        $page = isset($response->meta->pagination->current_page) ? $response->meta->pagination->current_page : ($params['page'] ?? 1);
        $options = [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $params['pageName'],
        ];
        $parsedResponse = Response::parse(json_encode($response));

        return new LengthAwarePaginator($parsedResponse, $totalPages, $perPage, $page, $options);
    }

    /**
     * Create a playerteam
     *
     * @param  array<string, mixed>  $attributes  Playerteam attributes
     * @param  array<string, mixed>  $relationships  Playerteam relationships
     * @return PlayerteamModel The created playerteam
     *
     * @throws InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): PlayerteamModel
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

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/playerteams', 'POST', $request);
        $formattedResponse = new Response(json_encode($response) ?: '{}');

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a playerteam by ID
     *
     * @param  string  $id  Playerteam ID
     * @param  array<string, mixed>  $params  Additional parameters (e.g., include relationships)
     * @return PlayerteamModel The playerteam
     *
     * @throws InvalidArgumentException
     */
    public function get(string $id, array $params = []): PlayerteamModel
    {
        $url = sprintf('/v1/playerteams/%s', $id);
        $response = $this->makeRequest($url, 'GET', ['query' => $params]);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Update a playerteam
     *
     * @param  string  $id  Playerteam ID
     * @param  array<string, mixed>  $attributes  Playerteam attributes to update
     * @param  array<string, mixed>  $relationships  Playerteam relationships to update
     * @return PlayerteamModel The updated playerteam
     *
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): PlayerteamModel
    {
        $url = sprintf('/v1/playerteams/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'playerteams',
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
     * Delete a playerteam
     *
     * @param  string  $id  Playerteam ID
     *
     * @throws InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = sprintf('/v1/playerteams/%s', $id);
        $this->makeRequest($url, 'DELETE');
    }
}
