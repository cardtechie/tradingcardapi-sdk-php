<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;

/**
 * Class Genre
 */
class Genre
{
    use ApiRequest;

    /**
     * Genre constructor.
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list()
    {
        return $this->makeRequest('/genres');
    }
}
