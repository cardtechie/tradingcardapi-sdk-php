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

    /**
     * Return a list of deleted genres.
     *
     * @return \stdClass
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deletedIndex()
    {
        $response = $this->makeRequest('/v1/genres/deleted');

        return Response::parse(json_encode($response));
    }

    /**
     * Return a specific deleted genre by ID.
     *
     * @return \stdClass
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleted(string $id)
    {
        $url = sprintf('/v1/genres/%s/deleted', $id);
        $response = $this->makeRequest($url);

        return Response::parse(json_encode($response));
    }
}
