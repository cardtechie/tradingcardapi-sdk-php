<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Models\Attribute as AttributeModel;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

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
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function create(array $attributes = []): AttributeModel
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

        $response = $this->makeRequest('/v1/attributes', 'POST', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Return a list of attributes.
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(): Collection
    {
        $response = $this->makeRequest('/v1/attributes');

        return Response::parse(json_encode($response));
    }

    /**
     * Retrieve an attribute.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id): AttributeModel
    {
        $url = sprintf('/v1/attributes/%s', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }

    /**
     * Update the attribute
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function update(string $id, array $attributes): AttributeModel
    {
        $url = sprintf('/v1/attributes/%s', $id);
        $request = [
            'json' => [
                'data' => [
                    'type' => 'attributes',
                    'id' => $id,
                    'attributes' => $attributes,
                ],
            ],
        ];
        $response = $this->makeRequest($url, 'PUT', $request);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
