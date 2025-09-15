<?php

namespace CardTechie\TradingCardApiSdk\Resources\Traits;

use CardTechie\TradingCardApiSdk\Services\ErrorResponseParser;
use CardTechie\TradingCardApiSdk\Services\ResponseValidator;
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
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * The response validator instance
     *
     * @var ResponseValidator|null
     */
    private $validator;

    /**
     * The error response parser instance
     *
     * @var ErrorResponseParser|null
     */
    private $errorParser;

    /**
     * Makes a request to an API endpoint or webpage and returns its response
     *
     * @param  string  $url  Url of the api or webpage
     * @param  string  $method  HTTP method
     * @param  array  $request  Additional parameters to include in the request
     * @param  array  $headers  HTTP headers
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException
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

        try {
            $response = $this->doRequest($url, $method, $theRequest);
        } catch (\Exception $exception) {
            if (! $this->errorParser) {
                $this->errorParser = new ErrorResponseParser;
            }
            throw $this->errorParser->parseGuzzleException($exception);
        }

        $body = (string) $response->getBody();

        if (empty($body)) {
            return new stdClass;
        }

        $jsonData = json_decode($body, true);

        // Validate response if validation is enabled
        if ($this->shouldValidate()) {
            $this->validateResponse($url, $jsonData);
        }

        return json_decode($body);
    }

    /**
     * Retrieve a token required for authentication
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException
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

        try {
            $response = $this->doRequest($url, 'POST', $request);
        } catch (\Exception $exception) {
            if (! $this->errorParser) {
                $this->errorParser = new ErrorResponseParser;
            }
            throw $this->errorParser->parseGuzzleException($exception);
        }

        $body = (string) $response->getBody();
        $json = json_decode($body);

        $this->token = $json->access_token;
        cache()->put($tokenKey, $this->token, 60);
    }

    /**
     * Perform the request with the client that has already been created.
     *
     * @param  string  $url  Url of the api or webpage
     * @param  string  $method  HTTP method
     * @param  array  $request  The request
     */
    private function doRequest(string $url, string $method = 'GET', array $request = []): ResponseInterface
    {
        return $this->client->request($method, $url, $request);
    }

    /**
     * Check if response validation should be performed
     */
    private function shouldValidate(): bool
    {
        return config('tradingcardapi.validation.enabled', true);
    }

    /**
     * Validate API response against expected schema
     *
     * @param  string  $url  The API endpoint URL
     * @param  array  $data  The response data
     */
    private function validateResponse(string $url, array $data): void
    {
        $resourceType = $this->extractResourceType($url);

        // Only validate if we can determine the resource type
        if ($resourceType) {
            if (! $this->validator) {
                $this->validator = new ResponseValidator;
            }

            $this->validator->validate($resourceType, $data, $url);
        }
    }

    /**
     * Extract resource type from API URL
     */
    private function extractResourceType(string $url): ?string
    {
        // Remove query parameters
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        // Match common API patterns
        if (preg_match('#/v\d+/([^/]+)#', $path, $matches)) {
            $resource = $matches[1];

            // Normalize resource names
            $normalizedResources = [
                'cards' => 'card',
                'players' => 'player',
                'teams' => 'team',
                'sets' => 'set',
                'genres' => 'genre',
                'brands' => 'brand',
                'manufacturers' => 'manufacturer',
                'years' => 'year',
                'attributes' => 'attribute',
                'object-attributes' => 'objectattribute',
                'playerteams' => 'playerteam',
                'stats' => 'stats',
            ];

            return $normalizedResources[$resource] ?? $resource;
        }

        return null;
    }

    /**
     * Get the response validator instance
     */
    public function getValidator(): ?ResponseValidator
    {
        return $this->validator;
    }

    /**
     * Get the error response parser instance
     */
    public function getErrorParser(): ?ErrorResponseParser
    {
        return $this->errorParser;
    }
}
