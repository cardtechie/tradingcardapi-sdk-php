<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Attribute as AttributeModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class Attribute
 */
class Attribute
{
    use ApiRequest;

    /**
     * Attribute constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create the attribute with the passed in attributes
     *
     * @param  array  $attributes  Attribute attributes
     * @param  array  $relationships  Attribute relationships
     * @return AttributeModel The created attribute
     *
     * @throws InvalidArgumentException
     */
    public function create(array $attributes = [], array $relationships = []): AttributeModel
    {
        $request = [
            'json' => [
                'data' => [
                    'type' => 'attributes',
                ],
            ],
        ];

        if (count($attributes)) {
            $request['json']['data']['attributes'] = $attributes;
        }

        if (count($relationships)) {
            $request['json']['data']['relationships'] = $relationships;
        }

        $response = $this->makeRequest('/v1/attributes', 'POST', $request);
        $formattedResponse = new Response(json_encode($response) ?: '{}');

        return $formattedResponse->mainObject;
    }

    /**
     * List attributes with pagination
     *
     * @param  array  $params  Query parameters (limit, page, sort, filters, etc.)
     * @return LengthAwarePaginator Paginated attribute results
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

        $url = sprintf('/v1/attributes?%s', http_build_query($params));
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
     * Return a raw collection of attributes.
     *
     * @param  array  $params  Query parameters
     * @return Collection The attributes collection
     *
     * @throws InvalidArgumentException
     */
    public function all(array $params = []): Collection
    {
        $query = http_build_query($params);
        $url = sprintf('/v1/attributes?%s', $query);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response) ?: '{}');
    }

    /**
     * Retrieve an attribute.
     *
     * @throws InvalidArgumentException
     */
    public function get(string $id): AttributeModel
    {
        $url = sprintf('/v1/attributes/%s', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response) ?: '{}');

        return $formattedResponse->mainObject;
    }

    /**
     * Update the attribute
     *
     * @param  string  $id  Attribute ID
     * @param  array  $attributes  Attribute attributes to update
     * @param  array  $relationships  Attribute relationships to update
     * @return AttributeModel The updated attribute
     *
     * @throws InvalidArgumentException
     */
    public function update(string $id, array $attributes = [], array $relationships = []): AttributeModel
    {
        $url = sprintf('/v1/attributes/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'attributes',
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
        $formattedResponse = new Response(json_encode($response) ?: '{}');

        return $formattedResponse->mainObject;
    }

    /**
     * Delete an attribute
     *
     * @param  string  $id  Attribute ID
     *
     * @throws InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = sprintf('/v1/attributes/%s', $id);
        $this->makeRequest($url, 'DELETE');
    }
}
