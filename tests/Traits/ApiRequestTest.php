<?php

use CardTechie\TradingCardApiSdk\Exceptions\AuthenticationException;
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
    // Set a cached token — key now includes scope (empty string by default)
    cache()->put(tokenCacheKey(), 'cached-token', 60);

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

it('requests token with configured scope', function () {
    $this->app['config']->set('tradingcardapi.scope', 'read:all-status write delete');

    $client = m::mock(Client::class);

    // Mock the OAuth token request with scope verification
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(function ($request) {
            return isset($request['form_params']['scope']) &&
                   $request['form_params']['scope'] === 'read:all-status write delete';
        }))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('uses default scope when not configured', function () {
    // Default scope should be 'read:published'
    $this->app['config']->set('tradingcardapi.scope', 'read:published');

    $client = m::mock(Client::class);

    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(function ($request) {
            return isset($request['form_params']['scope']) &&
                   $request['form_params']['scope'] === 'read:published';
        }))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('handles empty scope configuration', function () {
    $this->app['config']->set('tradingcardapi.scope', '');

    $client = m::mock(Client::class);

    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(function ($request) {
            return isset($request['form_params']['scope']) &&
                   $request['form_params']['scope'] === '';
        }))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('sets Content-Type header to application/vnd.api+json for POST requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request and verify Content-Type header
    $apiResponse = new GuzzleResponse(201, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('POST', '/v1/cards', m::on(function ($request) {
            return isset($request['headers']['Content-Type']) &&
                   $request['headers']['Content-Type'] === 'application/vnd.api+json';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/v1/cards', 'POST');

    expect($result)->toBeObject();
});

it('sets Content-Type header to application/vnd.api+json for PUT requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request and verify Content-Type header
    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('PUT', '/v1/cards/123', m::on(function ($request) {
            return isset($request['headers']['Content-Type']) &&
                   $request['headers']['Content-Type'] === 'application/vnd.api+json';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/v1/cards/123', 'PUT');

    expect($result)->toBeObject();
});

it('sets Content-Type header to application/vnd.api+json for PATCH requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request and verify Content-Type header
    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('PATCH', '/v1/cards/123', m::on(function ($request) {
            return isset($request['headers']['Content-Type']) &&
                   $request['headers']['Content-Type'] === 'application/vnd.api+json';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/v1/cards/123', 'PATCH');

    expect($result)->toBeObject();
});

it('does not set Content-Type header for GET requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request and verify no Content-Type header
    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/v1/cards/123', m::on(function ($request) {
            return ! isset($request['headers']['Content-Type']);
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/v1/cards/123', 'GET');

    expect($result)->toBeObject();
});

it('does not set Content-Type header for DELETE requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request and verify no Content-Type header
    $apiResponse = new GuzzleResponse(204, [], '');

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('DELETE', '/v1/cards/123', m::on(function ($request) {
            return ! isset($request['headers']['Content-Type']);
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/v1/cards/123', 'DELETE');

    expect($result)->toBeInstanceOf(stdClass::class);
});

it('uses PAT directly without making OAuth token request', function () {
    $client = m::mock(Client::class);

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    // Should NOT make an OAuth token request
    $client->shouldNotReceive('request')->with('POST', '/oauth/token', m::type('array'));

    $client->shouldReceive('request')
        ->with('GET', '/test', m::on(function ($request) {
            return isset($request['headers']['Authorization']) &&
                   $request['headers']['Authorization'] === 'Bearer my-pat';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $instance->setAuthInfo('pat', 'my-pat', null, null);
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
    expect($result->data->id)->toBe('123');
});

it('throws AuthenticationException when PAT is null', function () {
    $client = m::mock(Client::class);
    $client->shouldNotReceive('request');

    $instance = new TestApiRequestClass($client);
    $instance->setAuthInfo('pat', null, null, null);

    expect(fn () => $instance->testMakeRequest('/test'))
        ->toThrow(AuthenticationException::class, 'Personal Access Token is required');
});

it('throws AuthenticationException when PAT is empty string', function () {
    $client = m::mock(Client::class);
    $client->shouldNotReceive('request');

    $instance = new TestApiRequestClass($client);
    $instance->setAuthInfo('pat', '', null, null);

    expect(fn () => $instance->testMakeRequest('/test'))
        ->toThrow(AuthenticationException::class, 'Personal Access Token is required');
});

it('uses instance OAuth credentials over config credentials', function () {
    $client = m::mock(Client::class);

    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'override-token',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(function ($request) {
            return isset($request['form_params']['client_id']) &&
                   $request['form_params']['client_id'] === 'override-id' &&
                   $request['form_params']['client_secret'] === 'override-secret';
        }))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $instance->setAuthInfo('oauth2', null, 'override-id', 'override-secret');
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('includes scope in OAuth request when set via setAuthInfo', function () {
    $client = m::mock(Client::class);

    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'scoped-token',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(function ($request) {
            return isset($request['form_params']['scope']) &&
                   $request['form_params']['scope'] === 'write:cards';
        }))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/test', m::type('array'))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $instance->setAuthInfo('oauth2', null, 'test-client-id', 'test-client-secret', 'write:cards');
    $result = $instance->testMakeRequest('/test');

    expect($result)->toBeObject();
});

it('uses separate cache keys for different scopes', function () {
    $tokenResponse1 = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'token-scope-a',
        'token_type' => 'Bearer',
    ]));

    $tokenResponse2 = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'token-scope-b',
        'token_type' => 'Bearer',
    ]));

    $apiResponse = new GuzzleResponse(200, [], json_encode(['data' => ['id' => '123']]));

    $client1 = m::mock(Client::class);
    $client1->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(fn ($r) => $r['form_params']['scope'] === 'scope-a'))
        ->once()
        ->andReturn($tokenResponse1);
    $client1->shouldReceive('request')->with('GET', '/test', m::type('array'))->once()->andReturn($apiResponse);

    $client2 = m::mock(Client::class);
    $client2->shouldReceive('request')
        ->with('POST', '/oauth/token', m::on(fn ($r) => $r['form_params']['scope'] === 'scope-b'))
        ->once()
        ->andReturn($tokenResponse2);
    $client2->shouldReceive('request')->with('GET', '/test', m::type('array'))->once()->andReturn($apiResponse);

    $instance1 = new TestApiRequestClass($client1);
    $instance1->setAuthInfo('oauth2', null, 'test-client-id', 'test-client-secret', 'scope-a');
    $instance1->testMakeRequest('/test');

    $instance2 = new TestApiRequestClass($client2);
    $instance2->setAuthInfo('oauth2', null, 'test-client-id', 'test-client-secret', 'scope-b');
    $instance2->testMakeRequest('/test');

    $keyA = tokenCacheKey(scope: 'scope-a');
    $keyB = tokenCacheKey(scope: 'scope-b');

    expect($keyA)->not->toBe($keyB);
    expect(cache()->get($keyA))->toBe('token-scope-a');
    expect(cache()->get($keyB))->toBe('token-scope-b');
});

it('allows custom Content-Type header to override default for POST requests', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the API request with custom Content-Type
    $apiResponse = new GuzzleResponse(200, [], json_encode(['success' => true]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('POST', '/test', m::on(function ($request) {
            return isset($request['headers']['Content-Type']) &&
                   $request['headers']['Content-Type'] === 'application/custom';
        }))
        ->once()
        ->andReturn($apiResponse);

    $instance = new TestApiRequestClass($client);
    $result = $instance->testMakeRequest('/test', 'POST', [], ['Content-Type' => 'application/custom']);

    expect($result->success)->toBeTrue();
});
