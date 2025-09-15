<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Set as SetModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class Set
 */
class Set
{
    use ApiRequest;

    /**
     * Set constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create the set with the passed in attributes
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes): SetModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'sets',
                    'attributes' => $attributes,
                ],
            ],
        ];
        $response = $this->makeRequest('/v1/sets', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a set by ID
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): SetModel
    {
        $defaultParams = [
            'include' => 'genre,manufacturer,brand,year,parentset,subsets,checklist',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/sets/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a list of sets
     *
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

        $url = sprintf('/v1/sets?%s', http_build_query($params));
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
     * Update the set
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes): SetModel
    {
        $url = sprintf('/v1/sets/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'sets',
                    'id' => $id,
                    'attributes' => $attributes,
                ],
            ],
        ];
        $response = $this->makeRequest($url, 'PUT', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Get the checklist for a set
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checklist(string $id): object
    {
        $url = sprintf('/v1/sets/%s/checklist', $id);

        return $this->makeRequest($url, 'GET');
    }

    /**
     * Add the missing cards (as empty cards) to the specified set
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addMissingCards(string $id): object
    {
        $url = sprintf('/v1/sets/%s/checklist', $id);

        return $this->makeRequest($url, 'POST');
    }

    /**
     * Add the checklist to the set
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addChecklist(array $request, string $id): object
    {
        $url = sprintf('/v1/sets/%s/checklist', $id);

        return $this->makeRequest($url, 'POST', $request);
    }

    /**
     * Delete a set
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/v1/sets/'.$id;
        $this->makeRequest($url, 'DELETE');
    }
}
