<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Manufacturer as ManufacturerModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class Manufacturer
 */
class Manufacturer
{
    use ApiRequest;

    /**
     * Manufacturer constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a manufacturer with the passed in attributes
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): ManufacturerModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'manufacturers',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/manufacturers', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a manufacturer by ID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): ManufacturerModel
    {
        $defaultParams = [
            'include' => '',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/manufacturers/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a list of manufacturers
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

        $url = sprintf('/v1/manufacturers?%s', http_build_query($params));
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
     * Update a manufacturer
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): ManufacturerModel
    {
        $url = sprintf('/v1/manufacturers/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'manufacturers',
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
     * Delete a manufacturer
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/v1/manufacturers/'.$id;
        $this->makeRequest($url, 'DELETE');
    }
}
