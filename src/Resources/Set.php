<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use App\Models\Set as SetModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

/**
 * Class Set
 */
class Set
{
    use ApiRequest;

    /**
     * Set constructor.
     *
     * @param  Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create the set with the passed in attributes
     *
     * @param  array  $attributes
     * @return SetModel
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
        $response = $this->makeRequest('/sets', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a set by ID
     *
     * @param  string  $id
     * @param  array  $params
     * @return SetModel
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id, array $params = []): SetModel
    {
        $defaultParams = [
            'include' => 'genre,manufacturer,brand,year,parentset,subsets,checklist',
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/sets/%s?%s', $id, http_build_query($params));
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Retrieve a list of sets
     *
     * @param  array  $params
     * @return Collection
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(array $params = []): Collection
    {
        $defaultParams = [
            'limit' => 50,
        ];
        $params = array_merge($defaultParams, $params);

        $url = sprintf('/sets?%s', http_build_query($params));
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }

    /**
     * Update the set
     *
     * @param  string  $id
     * @param  array  $attributes
     * @return SetModel
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes): SetModel
    {
        $url = sprintf('/sets/%s', $id);
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
     * Add the missing cards (as empty cards) to the specified set
     *
     * @param  string  $id
     * @return object
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addMissingCards(string $id): object
    {
        $url = sprintf('/sets/%s/checklist', $id);

        return $this->makeRequest($url, 'POST');
    }

    /**
     * Add the checklist to the set
     *
     * @param  array  $request
     * @param  string  $id
     * @return object
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addChecklist(array $request, string $id): object
    {
        $url = sprintf('/sets/%s/checklist', $id);

        return $this->makeRequest($url, 'POST', $request);
    }

    /**
     * Delete a set
     *
     * @param  string  $id
     * @return void
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $id): void
    {
        $url = '/sets/'.$id;
        $this->makeRequest($url, 'DELETE');
    }
}
