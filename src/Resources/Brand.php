<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Brand as BrandModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class Brand
 */
class Brand
{
    use ApiRequest;

    /**
     * Brand constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a brand with the passed in attributes
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): BrandModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'brands',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/brands', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a brand by ID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): BrandModel
    {
        $defaultParams = [
            'include' => '',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/brands/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a list of brands
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

        $url = sprintf('/v1/brands?%s', http_build_query($params));
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
     * Update a brand
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): BrandModel
    {
        $url = sprintf('/v1/brands/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'brands',
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
     * Delete a brand
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/v1/brands/'.$id;
        $this->makeRequest($url, 'DELETE');
    }
}
