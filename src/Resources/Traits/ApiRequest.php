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
     * Authentication type ('oauth2' or 'pat')
     *
     * @var string
     */
    private $authType = 'oauth2';

    /**
     * Personal Access Token (for PAT auth mode)
     *
     * @var string|null
     */
    private $personalAccessToken;

    /**
     * OAuth2 Client ID (for OAuth2 auth mode)
     *
     * @var string|null
     */
    private $oauthClientId;

    /**
     * OAuth2 Client Secret (for OAuth2 auth mode)
     *
     * @var string|null
     */
    private $oauthClientSecret;

    /**
     * OAuth2 Scope (for OAuth2 auth mode)
     *
     * @var string|null
     */
    private $oauthScope;

    /**
     * Set authentication information
     *
     * @param  string  $authType  The authentication type ('oauth2' or 'pat')
     * @param  string|null  $personalAccessToken  The personal access token (for PAT mode)
     * @param  string|null  $clientId  The OAuth2 client ID (for OAuth2 mode)
     * @param  string|null  $clientSecret  The OAuth2 client secret (for OAuth2 mode)
     * @param  string|null  $scope  The OAuth2 scope (for OAuth2 mode)
     */
    public function setAuthInfo(
        string $authType,
        ?string $personalAccessToken = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $scope = null
    ): void {
        $this->authType = $authType;
        $this->personalAccessToken = $personalAccessToken;
        $this->oauthClientId = $clientId;
        $this->oauthClientSecret = $clientSecret;
        $this->oauthScope = $scope;
    }

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

        $isMultipart = isset($request['multipart']);

        $defaultRequest = [];
        $defaultHeaders = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->token,
            'X-TCAPI-Ignore-Status' => (string) config('tradingcardapi.ignore_status', 0),
        ];

        // For multipart requests, Guzzle will set Content-Type automatically
        // For JSON:API requests (POST/PUT/PATCH), we need to set the JSON:API Content-Type
        if (! $isMultipart && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $defaultHeaders['Content-Type'] = 'application/vnd.api+json';
        }

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
        // If using PAT authentication, use the personal access token directly
        if ($this->authType === 'pat') {
            if ($this->personalAccessToken) {
                $this->token = $this->personalAccessToken;

                return;
            }

            // Fallback to config if PAT not set via setAuthInfo
            $config = config('tradingcardapi');
            if (! empty($config['personal_access_token'])) {
                $this->token = $config['personal_access_token'];

                return;
            }

            throw new \RuntimeException('Personal Access Token not configured');
        }

        // OAuth2 Client Credentials flow
        // First, check if credentials were set via setAuthInfo() (e.g., from withClientCredentials())
        // This allows using TradingCardApi::withClientCredentials() without needing config values
        $clientId = $this->oauthClientId;
        $clientSecret = $this->oauthClientSecret;

        // If instance credentials are not set, fall back to config values
        if (! $clientId || ! $clientSecret) {
            $config = config('tradingcardapi');
            $clientId = $config['client_id'] ?? '';
            $clientSecret = $config['client_secret'] ?? '';
        }

        // Validate that we have non-empty credentials
        if (empty($clientId) || empty($clientSecret)) {
            throw new \RuntimeException('OAuth2 client credentials not configured. Please set TRADINGCARDAPI_CLIENT_ID and TRADINGCARDAPI_CLIENT_SECRET.');
        }

        // Generate unique cache key based on credentials to prevent token sharing
        // between different OAuth2 client instances
        $tokenKey = 'tcapi_token_'.md5($clientId.$clientSecret);

        if (cache()->has($tokenKey)) {
            $this->token = cache()->get($tokenKey);

            return;
        }

        $url = '/oauth/token';
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $this->oauthScope ?? '',
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
                'card-images' => 'cardimage',
                'players' => 'player',
                'teams' => 'team',
                'sets' => 'set',
                'set-sources' => 'setsource',
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
