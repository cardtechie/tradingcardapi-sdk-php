<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;

/**
 * Class Genre
 */
class Genre
{
    use ApiRequest;

    /**
     * Genre constructor.
     *
     * @param  Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Return a list of genres.
     *
     * @return \stdClass
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list()
    {
        $response = $this->makeRequest('/genres');

        return Response::parse(json_encode($response));
    }
}
