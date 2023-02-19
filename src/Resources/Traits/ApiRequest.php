<?php

namespace CardTechie\TradingCardApiSdk\Resources\Traits;

use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Trait ApiRequest
 */
trait ApiRequest
{
    /**
     * The oauth token
     *
     * @var string
     */
    private $token;

    /**
     * The client to make API requests
     *
     * @var /Guzzle/Http/Client
     */
    private $client;

    /**
     * Makes a request to an API endpoint or webpage and returns its response
     *
     * @param  string  $url     Url of the api or webpage
     * @param  string  $method  HTTP method
     * @param  array  $request Additional parameters to include in the request
     * @param  array  $headers HTTP headers
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function makeRequest(string $url, string $method = 'GET', array $request = [], array $headers = []): object
    {
        $this->retrieveToken();

        $defaultRequest = [];
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->token,
            'X-TCAPI-Ignore-Status' => (string) env('TRADINGCARDAPI_IGNORE_STATUS', 0),
        ];

        $theRequest = array_merge($defaultRequest, $request);
        $theRequest['headers'] = array_merge($defaultHeaders, $headers);

        $response = $this->doRequest($url, $method, $theRequest);
        $body = (string) $response->getBody();

        if (empty($body)) {
            return new stdClass();
        }

        return (object) json_decode($body);
    }

    /**
     * Retrieve a token required for authentication
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    private function retrieveToken(): void
    {
        $tokenKey = 'tcapi_token';
        if (cache()->has($tokenKey)) {
            $this->token = cache()->get($tokenKey);

            return;
        }

        $config = config('tradingcardapi');

        $url = '/oauth/token';
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'scope' => '',
        ];

        $request = [];
        $request['headers'] = $headers;
        $request['form_params'] = $body;

        $response = $this->doRequest($url, 'POST', $request);
        $body = (string) $response->getBody();
        $json = json_decode($body);

        $this->token = $json->access_token;
        cache()->put($tokenKey, $this->token, 60);
    }

    /**
     * Perform the request with the client that has already been created.
     *
     * @param  string  $url     Url of the api or webpage
     * @param  string  $method  HTTP method
     * @param  array  $request The request
     */
    private function doRequest(string $url, string $method = 'GET', array $request = []): ResponseInterface
    {
        return $this->client->request($method, $url, $request);
    }
}
