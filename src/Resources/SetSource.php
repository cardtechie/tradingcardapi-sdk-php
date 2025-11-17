<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\SetSource as SetSourceModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class SetSource
 *
 * Handles set source operations for tracking data provenance.
 */
class SetSource
{
    use ApiRequest;

    /**
     * SetSource constructor.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve a list of set sources
     *
     * @param  array  $params  Query parameters (filter, include, page, limit, etc.)
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

        $url = sprintf('/v1/set-sources?%s', http_build_query($params));
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
     * Retrieve a set source by ID
     *
     * @param  string  $id  Set source UUID
     * @param  array  $params  Query parameters (include, etc.)
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): SetSourceModel
    {
        $defaultParams = [
            'include' => 'set',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/v1/set-sources/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Create a new set source
     *
     * @param  array  $attributes  Set source attributes
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes): SetSourceModel
    {
        $url = '/v1/set-sources';
        $request = [
            'json' => [
                'data' => [
                    'type' => 'set-sources',
                    'attributes' => $attributes,
                ],
            ],
        ];

        $response = $this->makeRequest($url, 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Update set source
     *
     * @param  string  $id  Set source UUID
     * @param  array  $attributes  Attributes to update
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes): SetSourceModel
    {
        $url = sprintf('/v1/set-sources/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'set-sources',
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
     * Delete a set source
     *
     * @param  string  $id  Set source UUID
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/v1/set-sources/'.$id;
        $this->makeRequest($url, 'DELETE');
    }
}
