<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Resources\Traits;

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;
use CardTechie\TradingCardApiSdk\Services\ErrorResponseParser;
use CardTechie\TradingCardApiSdk\Services\ResponseValidator;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use stdClass;

/**
 * Trait ApiRequest
 */
trait ApiRequest
{
    /**
     * The oauth token
     */
    private ?string $token = null;

    /**
     * The client to make API requests
     */
    private Client $client;

    /**
     * The response validator instance
     */
    private ?ResponseValidator $validator = null;

    /**
     * The error response parser instance
     */
    private ?ErrorResponseParser $errorParser = null;

    /**
     * Authentication type ('oauth2' or 'pat')
     */
    private string $authType = 'oauth2';

    /**
     * Personal Access Token (for PAT auth mode)
     */
    private ?string $personalAccessToken = null;

    /**
     * OAuth2 Client ID
     */
    private ?string $oauthClientId = null;

    /**
     * OAuth2 Client Secret
     */
    private ?string $oauthClientSecret = null;

    /**
     * OAuth2 Scope
     */
    private ?string $scope = null;

    /**
     * Set authentication information on this resource.
     */
    public function setAuthInfo(string $authType, ?string $personalAccessToken, ?string $clientId, ?string $clientSecret, ?string $scope = null): void
    {
        $this->authType = $authType;
        $this->personalAccessToken = $personalAccessToken;
        $this->oauthClientId = $clientId;
        $this->oauthClientSecret = $clientSecret;
        $this->scope = $scope;
    }

    /**
     * Makes a request to a JSON API endpoint and returns its decoded response.
     *
     * This is the low-level transport primitive: it deliberately returns the
     * raw `json_decode` result (a `stdClass` tree, or an empty `stdClass` for
     * an empty body) without normalizing it to a Model or DTO. Resource
     * methods are responsible for mapping this raw object onto their declared
     * return type (a typed Model/DTO, or a documented raw object for genuinely
     * unstructured endpoints). Callers should prefer the typed resource
     * methods over calling `makeRequest` directly.
     *
     * @param  string  $url  Url of the JSON API endpoint
     * @param  string  $method  HTTP method
     * @param  array  $request  Additional parameters to include in the request
     * @param  array  $headers  HTTP headers
     * @return object The raw decoded response (unstructured)
     *
     * @throws InvalidArgumentException
     * @throws TradingCardApiException
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

        $decoded = json_decode($body);

        // Validate response if validation is enabled; decode as array only when needed
        if ($this->shouldValidate()) {
            $this->validateResponse($url, json_decode($body, true));
        }

        return $decoded;
    }

    /**
     * Build the cache key used to store an OAuth token.
     *
     * @internal Intended for use by this trait and test helpers only.
     */
    public static function buildTokenCacheKey(string $clientId, string $clientSecret, string $scope = ''): string
    {
        return 'tcapi_token_'.md5($clientId.'|'.$clientSecret.'|'.$scope);
    }

    /**
     * Retrieve a token required for authentication
     *
     * @throws InvalidArgumentException
     * @throws TradingCardApiException
     */
    private function retrieveToken(): void
    {
        // PAT path: skip OAuth entirely, use token directly
        if ($this->authType === 'pat') {
            if (empty($this->personalAccessToken)) {
                throw new AuthenticationException('Personal Access Token is required');
            }
            $this->token = $this->personalAccessToken;

            return;
        }

        // OAuth2 path: use instance credentials if set, fall back to config
        $config = config('tradingcardapi');
        $clientId = $this->oauthClientId ?? $config['client_id'];
        $clientSecret = $this->oauthClientSecret ?? $config['client_secret'];
        $scope = $this->scope ?? $config['scope'] ?? '';

        // Include scope in cache key so different scopes don't collide
        $tokenKey = static::buildTokenCacheKey($clientId, $clientSecret, $scope);

        if (cache()->has($tokenKey)) {
            $this->token = cache()->get($tokenKey);

            return;
        }

        $url = '/oauth/token';
        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
            ],
        ];

        try {
            $response = $this->doRequest($url, 'POST', $request);
        } catch (\Exception $exception) {
            if (! $this->errorParser) {
                $this->errorParser = new ErrorResponseParser;
            }
            throw $this->errorParser->parseGuzzleException($exception);
        }

        $json = json_decode((string) $response->getBody());
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

        // Normalize resource names (shared by the /v<n>/ and /internal/ branches)
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
            'object-attributes' => 'object-attribute',
            'playerteams' => 'playerteam',
            'stats' => 'stats',
            'card-images' => 'card-image',
            'set-sources' => 'set-source',
            'audit-logs' => 'audit-log',
            'workflow' => 'workflow',
            'set-todos' => 'set-todo',
        ];

        // Match common API patterns
        if (preg_match('#/v\d+/([^/]+)#', $path, $matches)) {
            // Sub-resource paths (e.g. /v1/sets/123/workflow) are not JSON:API
            // resource responses — skip validation to avoid false failures
            if (preg_match('#/v\d+/[^/]+/[^/]+/.+#', $path)) {
                return null;
            }

            $resource = $matches[1];

            return $normalizedResources[$resource] ?? $resource;
        }

        // Internal endpoints (e.g. /internal/set-todos/{id}, /internal/audit-logs).
        // The /v<n>/ regex never matches these, so without this branch the
        // workflow/set-todo/audit-log schema mappings would be dead for their
        // real URLs.
        if (preg_match('#^/internal/([^/]+)(?:/([^/]+))?(/.+)?$#', $path, $matches)) {
            $resource = $matches[1];
            $second = $matches[2] ?? null;
            // The trailing (/.+)? group only participates for paths deeper than
            // /internal/<resource>/<segment>; preg_match omits it otherwise, so a
            // non-empty third match means there are extra path segments.
            $hasDeeperPath = ! empty($matches[3]);

            // Anything deeper than /internal/<resource>/<segment> is a
            // multi-segment sub-resource (e.g. /internal/workflow/sets/{id}/todos
            // or /internal/sets/{id}/workflow) and is not a single JSON:API
            // resource response — skip validation.
            if ($hasDeeperPath) {
                return null;
            }

            // The workflow namespace exposes only named sub-resource/action
            // endpoints (actionable-sets, bulk-initialize, ...), never a single
            // /internal/workflow/<id> resource fetch. So a second segment under
            // workflow is always an action — skip validation. Other internal
            // resources (set-todos, audit-logs) treat a second segment as a
            // record id and validate the single-resource response.
            if ($second !== null && $resource === 'workflow') {
                return null;
            }

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
