<?php

namespace CardTechie\TradingCardApiSdk\Resources;

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;
use stdClass;

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
     * @return stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function list(): stdClass
    {
        return $this->makeRequest('/attributes');
    }
}
