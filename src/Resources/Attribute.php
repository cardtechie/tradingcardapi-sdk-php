<?php

namespace CardTechie\TradingCardApiSdk\Resources;

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
     *
     * @param  Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Return a list of attributes.
     *
     * @return Collection
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(): Collection
    {
        $response = $this->makeRequest('/attributes');

        return Response::parse(json_encode($response));
    }
}
