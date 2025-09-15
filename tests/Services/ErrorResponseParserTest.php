<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
use CardTechie\TradingCardApiSdk\Exceptions\AuthorizationException;
use CardTechie\TradingCardApiSdk\Exceptions\CardNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\NetworkException;
use CardTechie\TradingCardApiSdk\Exceptions\RateLimitException;
use CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException;
use CardTechie\TradingCardApiSdk\Exceptions\ServerException;
use CardTechie\TradingCardApiSdk\Exceptions\TradingCardApiException;
use CardTechie\TradingCardApiSdk\Exceptions\ValidationException;
use CardTechie\TradingCardApiSdk\Services\ErrorResponseParser;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->parser = new ErrorResponseParser;
});

it('parses Guzzle connect exception as network exception', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new ConnectException('Connection timed out', $request);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(NetworkException::class);
    expect($exception->getMessage())->toContain('Connection timed out');
});

it('parses 401 response as authentication exception', function () {
    $response = new Response(401, [], json_encode([
        'error' => 'invalid_credentials',
        'message' => 'Invalid API credentials',
    ]));
    $request = new Request('GET', '/api/test');
    $guzzleException = new ClientException('401 Unauthorized', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(AuthenticationException::class);
    expect($exception->getMessage())->toBe('Invalid API credentials');
    expect($exception->getHttpStatusCode())->toBe(401);
});

it('parses 403 response as authorization exception', function () {
    $response = new Response(403, [], json_encode([
        'message' => 'Access forbidden',
    ]));
    $request = new Request('GET', '/api/protected');
    $guzzleException = new ClientException('403 Forbidden', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(AuthorizationException::class);
    expect($exception->getMessage())->toBe('Access forbidden');
    expect($exception->getHttpStatusCode())->toBe(403);
});

it('parses 404 response as resource not found exception', function () {
    $response = new Response(404, [], json_encode([
        'message' => 'Card not found',
    ]));
    $request = new Request('GET', '/api/cards/123');
    $guzzleException = new ClientException('404 Not Found', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(ResourceNotFoundException::class);
    expect($exception->getMessage())->toBe('Card not found');
    expect($exception->getHttpStatusCode())->toBe(404);
});

it('parses 422 response as validation exception', function () {
    $response = new Response(422, [], json_encode([
        'message' => 'Validation failed',
        'errors' => [
            [
                'title' => 'Validation Error',
                'detail' => 'Name is required',
                'source' => ['parameter' => 'name'],
            ],
        ],
    ]));
    $request = new Request('POST', '/api/cards');
    $guzzleException = new ClientException('422 Unprocessable Entity', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(ValidationException::class);
    expect($exception->getMessage())->toBe('Validation failed');
    expect($exception->getHttpStatusCode())->toBe(422);
    expect($exception->hasFieldError('name'))->toBeTrue();
});

it('parses 429 response as rate limit exception', function () {
    $response = new Response(429, [
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '0',
        'Retry-After' => '300',
    ], json_encode([
        'message' => 'Rate limit exceeded',
    ]));
    $request = new Request('GET', '/api/cards');
    $guzzleException = new ClientException('429 Too Many Requests', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(RateLimitException::class);
    expect($exception->getMessage())->toBe('Rate limit exceeded');
    expect($exception->getHttpStatusCode())->toBe(429);
    expect($exception->getRateLimit())->toBe(1000);
    expect($exception->getRetryAfter())->toBe(300);
});

it('parses 500 response as server exception', function () {
    $response = new Response(500, [], json_encode([
        'message' => 'Internal server error',
    ]));
    $request = new Request('GET', '/api/cards');
    $guzzleException = new GuzzleServerException('500 Internal Server Error', $request, $response);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(ServerException::class);
    expect($exception->getMessage())->toBe('Internal server error');
    expect($exception->getHttpStatusCode())->toBe(500);
});

it('extracts JSON:API format errors', function () {
    $responseData = [
        'errors' => [
            [
                'title' => 'Validation Error',
                'detail' => 'Name is required',
                'source' => ['parameter' => 'name'],
            ],
            [
                'title' => 'Validation Error',
                'detail' => 'Email is invalid',
                'source' => ['parameter' => 'email'],
            ],
        ],
    ];

    $response = new Response(422, [], json_encode($responseData));

    $exception = $this->parser->parseHttpResponse($response);

    expect($exception->getApiErrors())->toHaveCount(2);
    expect($exception->getApiErrors()[0]['title'])->toBe('Validation Error');
    expect($exception->getApiErrors()[0]['detail'])->toBe('Name is required');
});

it('extracts Laravel validation format errors', function () {
    $responseData = [
        'message' => 'Validation failed',
        'errors' => [
            'name' => ['Name is required'],
            'email' => ['Email is required', 'Email is invalid'],
        ],
    ];
    // This should create: 1 error for name + 2 errors for email = 3 total

    $response = new Response(422, [], json_encode($responseData));

    $exception = $this->parser->parseHttpResponse($response);

    expect($exception->getApiErrors())->toHaveCount(3); // 1 for name + 2 for email
    expect($exception->getApiErrors()[0]['detail'])->toBe('Name is required');
    expect($exception->getApiErrors()[0]['source']['parameter'])->toBe('name');
});

it('handles empty response body gracefully', function () {
    $response = new Response(500, [], '');

    $exception = $this->parser->parseHttpResponse($response);

    expect($exception)->toBeInstanceOf(ServerException::class);
    expect($exception->getMessage())->toBe('Internal server error');
});

it('handles malformed JSON response gracefully', function () {
    $response = new Response(400, [], 'Invalid JSON {');

    $exception = $this->parser->parseHttpResponse($response);

    expect($exception)->toBeInstanceOf(TradingCardApiException::class);
    expect($exception->getContext()['parsed_response']['raw_body'])->toBe('Invalid JSON {');
});

it('creates specific resource not found exceptions', function () {
    $cardException = $this->parser->createResourceNotFoundException('card', '123');
    expect($cardException)->toBeInstanceOf(CardNotFoundException::class);
    expect($cardException->getMessage())->toContain('card');
    expect($cardException->getMessage())->toContain('123');

    $genericException = $this->parser->createResourceNotFoundException('unknown', '456');
    expect($genericException)->toBeInstanceOf(ResourceNotFoundException::class);
    expect($genericException->getMessage())->toContain('unknown');
    expect($genericException->getMessage())->toContain('456');
});

it('defaults to generic message when none provided', function () {
    $response = new Response(418, [], '{}'); // I'm a teapot

    $exception = $this->parser->parseHttpResponse($response);

    expect($exception->getMessage())->toBe('HTTP 418 error');
    expect($exception->getHttpStatusCode())->toBe(418);
});

it('handles error with error_description field', function () {
    $responseData = [
        'error' => 'invalid_grant',
        'error_description' => 'The provided authorization grant is invalid',
    ];

    $response = new Response(400, [], json_encode($responseData));
    $exception = $this->parser->parseHttpResponse($response);

    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['detail'])->toBe('The provided authorization grant is invalid');
    expect($exception->getMessage())->toBe('The provided authorization grant is invalid');
});

it('handles simple error string', function () {
    $responseData = ['error' => 'custom_error'];

    $response = new Response(400, [], json_encode($responseData));
    $exception = $this->parser->parseHttpResponse($response);

    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['detail'])->toBe('custom_error');
});

it('parses headers correctly', function () {
    $headers = [
        'Content-Type' => ['application/json'],
        'X-Custom-Header' => ['value1', 'value2'],
    ];

    $response = new Response(200, $headers, '{}');
    $exception = $this->parser->parseHttpResponse($response);

    $context = $exception->getContext();
    expect($context['headers']['Content-Type'])->toBe('application/json');
    expect($context['headers']['X-Custom-Header'])->toBe('value1, value2');
});

it('handles RequestException without response', function () {
    $request = new Request('GET', 'https://api.example.com');
    $guzzleException = new \GuzzleHttp\Exception\RequestException('Request failed', $request);

    $exception = $this->parser->parseGuzzleException($guzzleException);

    expect($exception)->toBeInstanceOf(NetworkException::class);
    expect($exception->getMessage())->toBe('HTTP request failed: Request failed');
    expect($exception->getApiErrorCode())->toBe('request_failed');
});

it('handles generic exceptions', function () {
    $genericException = new \Exception('Something went wrong');

    $exception = $this->parser->parseGuzzleException($genericException);

    expect($exception)->toBeInstanceOf(TradingCardApiException::class);
    expect($exception->getMessage())->toBe('Unexpected error: Something went wrong');
    expect($exception->getApiErrorCode())->toBe('unexpected_error');
    expect($exception->getPrevious())->toBe($genericException);
});
