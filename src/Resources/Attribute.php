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
     * Return a list of attributes.
     *
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(): Collection
    {
        $response = $this->makeRequest('/attributes');

        return Response::parse(json_encode($response));
    }

    /**
     * Retrieve an attribute.
     *
     * @param string $id
     * @return AttributeModel
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $id): AttributeModel
    {
        $url = sprintf('/attributes/%s', $id);
        $response = $this->makeRequest($url);
        $formattedResponse = new Response(json_encode($response));

        return $formattedResponse->mainObject;
    }
}
