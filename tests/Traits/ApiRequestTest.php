<?php

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery as m;

class TestApiRequestClass
{
    use ApiRequest;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function testMakeRequest($url, $method = 'GET', $request = [], $headers = [])
    {
        return $this->makeRequest($url, $method, $request, $headers);
    }
}

beforeEach(function () {
    $this->app['config']->set('tradingcardapi', [
        'url' => 'https://api.example.com',
        'ssl_verify' => true,
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);

    cache()->flush();
});

afterEach(function () {
    m::close();
});

it('can make a request with token retrieval', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the actual API request
    $apiResponse = new GuzzleResponse(200, [], json_encode([
        'data' => ['id' => '123', 'name' => 'Test'],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('123');
});

it('uses cached token when available', function () {
    // Set a cached token with the new cache key format (includes credentials hash)
    $cacheKey = 'tcapi_token_'.md5('test-client-idtest-client-secret');
    cache()->put($cacheKey, 'cached-token', 60);

    $client = m::mock(Client::class);

    // Mock only the API request (no token request should be made)
    $apiResponse = new GuzzleResponse(200, [], json_encode([
        'data' => ['id' => '123', 'name' => 'Test'],
    ]));

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    // Should NOT receive a token request
    $client->shouldNotReceive('request')->with('POST', '/oauth/token', m::type('array'));

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('handles empty response body', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock empty response
    $apiResponse = new GuzzleResponse(204, [], '');

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('DELETE', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test', 'DELETE');

    expect($result)->toBeInstanceOf(stdClass::class);
});

it('includes custom headers in request', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request with custom headers
    $apiResponse = new GuzzleResponse(200, [], json_encode(['success' => true]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('POST', '/test', m::on(function ($request) {
            return isset($request['headers']['Custom-Header']) &&
                   $request['headers']['Custom-Header'] === 'custom-value';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test', 'POST', [], ['Custom-Header' => 'custom-value']);

    expect($result->success)->toBeTrue();
});
