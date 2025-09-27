<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Team as TeamModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * @param  array  $attributes  Team attributes
     * @param  array  $relationships  Team relationships
     * @return TeamModel The created team
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): TeamModel
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

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/teams', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a team by ID
     *
     * @param  string  $id  Team ID
     * @param  array  $params  Additional parameters (e.g., include relationships)
     * @return TeamModel The team
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): TeamModel
    {
        $url = sprintf('/v1/teams/%s', $id);
        $response = $this->makeRequest($url, 'GET', ['query' => $params]);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * List teams with pagination
     *
     * @param  array  $params  Query parameters (limit, page, sort, filters, etc.)
     * @return LengthAwarePaginator Paginated team results
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

        $url = sprintf('/v1/teams?%s', http_build_query($params));
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
     * Update a team
     *
     * @param  string  $id  Team ID
     * @param  array  $attributes  Team attributes to update
     * @param  array  $relationships  Team relationships to update
     * @return TeamModel The updated team
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): TeamModel
    {
        $url = sprintf('/v1/teams/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'teams',
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
     * Delete a team
     *
     * @param  string  $id  Team ID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = sprintf('/v1/teams/%s', $id);
        $this->makeRequest($url, 'DELETE');
    }

    /**
     * List deleted teams
     *
     * @return LengthAwarePaginator Paginated deleted team results
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function listDeleted(): LengthAwarePaginator
    {
        $response = $this->makeRequest('/v1/teams/deleted');

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
     * Get a deleted team by ID
     *
     * @param  string  $id  Team ID
     * @return TeamModel The deleted team
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleted(string $id): TeamModel
    {
        $url = sprintf('/v1/teams/%s/deleted', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
