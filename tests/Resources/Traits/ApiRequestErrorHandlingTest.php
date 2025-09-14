<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\NetworkException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Services\ErrorResponseParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class ApiRequestTestClass
{
    use ApiRequest;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function testMakeRequest(string $url, string $method = 'GET', array $request = [], array $headers = []): object
    {
        return $this->makeRequest($url, $method, $request, $headers);
    }
}

beforeEach(function () {
    // Mock config function for testing
    if (! function_exists('config')) {
        function config($key = null, $default = null)
        {
            $config = [
                'tradingcardapi.client_id' => 'test_client_id',
                'tradingcardapi.client_secret' => 'test_client_secret',
                'tradingcardapi.validation.enabled' => false, // Disable validation for error handling tests
            ];

            if ($key === null) {
                return $config;
            }

            return $config[$key] ?? $default;
        }
    }

    // Mock cache function for testing
    if (! function_exists('cache')) {
        function cache()
        {
            return new class
            {
                private $data = ['tcapi_token' => 'test_token'];

                public function has($key)
                {
                    return isset($this->data[$key]) && $this->data[$key] !== null;
                }

                public function get($key)
                {
                    return $this->data[$key] ?? null;
                }

                public function put($key, $value, $ttl)
                {
                    $this->data[$key] = $value;
                }
            };
        }
    }

    // Mock env function for testing
    if (! function_exists('env')) {
        function env($key, $default = null)
        {
            return $default;
        }
    }
});

it('throws authentication exception on 401 response', function () {
    $mock = new MockHandler([
        new Response(401, [], json_encode([
            'error' => 'invalid_credentials',
            'message' => 'Invalid API credentials',
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    expect(fn () => $apiRequest->testMakeRequest('/api/test'))
        ->toThrow(AuthenticationException::class);
});

it('throws resource not found exception on 404 response', function () {
    $mock = new MockHandler([
        new Response(404, [], json_encode([
            'message' => 'Card not found',
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    expect(fn () => $apiRequest->testMakeRequest('/api/cards/123'))
        ->toThrow(ResourceNotFoundException::class);
});

it('throws validation exception on 422 response', function () {
    $mock = new MockHandler([
        new Response(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => [
                [
                    'title' => 'Validation Error',
                    'detail' => 'Name is required',
                    'source' => ['parameter' => 'name'],
                ],
            ],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    $exception = null;
    try {
        $apiRequest->testMakeRequest('/api/cards', 'POST');
    } catch (ValidationException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ValidationException::class);
    expect($exception->hasFieldError('name'))->toBeTrue();
});

it('throws server exception on 500 response', function () {
    $mock = new MockHandler([
        new Response(500, [], json_encode([
            'message' => 'Internal server error',
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    expect(fn () => $apiRequest->testMakeRequest('/api/cards'))
        ->toThrow(ServerException::class);
});

it('throws network exception on connection failure', function () {
    $request = new Request('GET', 'https://api.example.com');
    $mock = new MockHandler([
        new ConnectException('Connection timed out', $request),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    expect(fn () => $apiRequest->testMakeRequest('/api/cards'))
        ->toThrow(NetworkException::class);
});

it('preserves exception context and details', function () {
    $errorResponse = [
        'error' => 'card_not_found',
        'message' => 'The specified card was not found',
        'errors' => [
            [
                'title' => 'Card Not Found',
                'detail' => 'No card with ID 123 exists',
            ],
        ],
    ];

    $mock = new MockHandler([
        new Response(404, ['Content-Type' => 'application/json'], json_encode($errorResponse)),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    $apiRequest = new ApiRequestTestClass($client);

    $exception = null;
    try {
        $apiRequest->testMakeRequest('/api/cards/123');
    } catch (ResourceNotFoundException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ResourceNotFoundException::class);
    expect($exception->getMessage())->toBe('The specified card was not found');
    expect($exception->getApiErrorCode())->toBe('card_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getContext())->toHaveKey('http_status_code', 404);
    expect($exception->getContext())->toHaveKey('parsed_response');
});

it('provides access to error parser instance', function () {
    $tokenResponse = ['access_token' => 'test_token_123'];
    $mock = new MockHandler([
        new Response(200, [], json_encode($tokenResponse)),
        new Response(200, [], '{}'),
        new Response(404, [], '{}'),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    // Clear cached token
    cache()->put('tcapi_token', null, 0);

    $apiRequest = new ApiRequestTestClass($client);

    // Make a successful request first
    $apiRequest->testMakeRequest('/api/test');
    expect($apiRequest->getErrorParser())->toBeNull(); // Not initialized unless exception occurs

    // Trigger an exception to initialize error parser
    try {
        $apiRequest->testMakeRequest('/api/not-found');
    } catch (\Exception $e) {
        // Exception expected
    }

    expect($apiRequest->getErrorParser())->toBeInstanceOf(ErrorResponseParser::class);
});

it('handles authentication errors during token retrieval', function () {
    // Mock token request failure
    $mock = new MockHandler([
        new Response(401, [], json_encode([
            'error' => 'invalid_client',
            'message' => 'Client authentication failed',
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    // Create fresh instance without cached token
    if (function_exists('cache')) {
        cache()->put('tcapi_token', null, 0); // Clear token
    }

    $apiRequest = new ApiRequestTestClass($client);

    expect(fn () => $apiRequest->testMakeRequest('/api/cards'))
        ->toThrow(AuthenticationException::class);
});

it('successfully makes request when no errors occur', function () {
    // Mock token response first, then success response
    $tokenResponse = ['access_token' => 'test_token_123'];
    $successResponse = [
        'data' => [
            'id' => '123',
            'type' => 'card',
            'attributes' => ['name' => 'Test Card'],
        ],
    ];

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode($tokenResponse)), // Token request
        new Response(200, ['Content-Type' => 'application/json'], json_encode($successResponse)), // Actual request
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    // Clear cached token to force token retrieval
    cache()->put('tcapi_token', null, 0);

    $apiRequest = new ApiRequestTestClass($client);
    $result = $apiRequest->testMakeRequest('/api/cards/123');

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('123');
    expect($result->data->attributes->name)->toBe('Test Card');
});
