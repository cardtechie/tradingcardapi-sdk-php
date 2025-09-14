<?php

use CardTechie\TradingCardApiSdk\Resources\Traits\ApiRequest;
use CardTechie\TradingCardApiSdk\Services\ResponseValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery as m;

// Create a test class that uses the ApiRequest trait
class TestApiResource
{
    use ApiRequest;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    // Expose protected methods for testing
    public function testExtractResourceType(string $url): ?string
    {
        return $this->extractResourceType($url);
    }

    public function testShouldValidate(): bool
    {
        return $this->shouldValidate();
    }

    public function testValidateResponse(string $url, array $data): void
    {
        $this->validateResponse($url, $data);
    }
}

beforeEach(function () {
    Cache::shouldReceive('has')->andReturn(true);
    Cache::shouldReceive('get')->andReturn('test_token');

    Config::set('tradingcardapi.validation', [
        'enabled' => true,
        'strict_mode' => false,
        'log_validation_errors' => true,
    ]);
});

it('extracts resource type from API URLs correctly', function () {
    $client = m::mock(Client::class);
    $resource = new TestApiResource($client);

    expect($resource->testExtractResourceType('/v1/cards'))->toBe('card');
    expect($resource->testExtractResourceType('/v1/cards/123'))->toBe('card');
    expect($resource->testExtractResourceType('/v1/players'))->toBe('player');
    expect($resource->testExtractResourceType('/v1/sets/456/checklist'))->toBe('set');
    expect($resource->testExtractResourceType('/v1/genres'))->toBe('genre');
    expect($resource->testExtractResourceType('/v1/object-attributes'))->toBe('objectattribute');
    expect($resource->testExtractResourceType('/v1/playerteams'))->toBe('playerteam');
    expect($resource->testExtractResourceType('/v1/stats/cards'))->toBe('stats');
});

it('returns null for non-API URLs', function () {
    $client = m::mock(Client::class);
    $resource = new TestApiResource($client);

    expect($resource->testExtractResourceType('/oauth/token'))->toBeNull();
    expect($resource->testExtractResourceType('/some/other/path'))->toBeNull();
    expect($resource->testExtractResourceType('invalid-url'))->toBeNull();
});

it('respects validation configuration', function () {
    $client = m::mock(Client::class);
    $resource = new TestApiResource($client);

    // Validation enabled
    Config::set('tradingcardapi.validation.enabled', true);
    expect($resource->testShouldValidate())->toBeTrue();

    // Validation disabled
    Config::set('tradingcardapi.validation.enabled', false);
    expect($resource->testShouldValidate())->toBeFalse();
});

it('validates response and creates validator instance', function () {
    $client = m::mock(Client::class);
    $resource = new TestApiResource($client);

    $validData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
    ];

    // Should not throw an exception with valid data
    $resource->testValidateResponse('/v1/cards/123', $validData);

    // Should have created validator instance
    expect($resource->getValidator())->toBeInstanceOf(ResponseValidator::class);
});

it('integrates validation with makeRequest method', function () {
    $client = m::mock(Client::class);

    $validResponseData = [
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'number' => '1',
            ],
        ],
    ];

    $responseBody = json_encode($validResponseData);
    $apiResponse = new GuzzleResponse(200, [], $responseBody);

    $client->shouldReceive('request')
        ->once()
        ->with('GET', '/v1/cards/123', m::type('array'))
        ->andReturn($apiResponse);

    $resource = new TestApiResource($client);

    // Should successfully make request and validate response
    $result = $resource->makeRequest('/v1/cards/123');

    expect($result)->toBeObject();
    expect($result->data)->toBeObject();
    expect($result->data->id)->toBe('123');

    // Should have validator instance
    expect($resource->getValidator())->toBeInstanceOf(ResponseValidator::class);
    expect($resource->getValidator()->isValid())->toBeTrue();
});

it('handles validation errors in lenient mode', function () {
    $client = m::mock(Client::class);

    $invalidResponseData = [
        'data' => [
            // Missing required 'id' field
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
            ],
        ],
    ];

    $responseBody = json_encode($invalidResponseData);
    $apiResponse = new GuzzleResponse(200, [], $responseBody);

    $client->shouldReceive('request')
        ->once()
        ->andReturn($apiResponse);

    $resource = new TestApiResource($client);

    // Should not throw exception in lenient mode
    $result = $resource->makeRequest('/v1/cards/123');

    expect($result)->toBeObject();
    expect($resource->getValidator())->toBeInstanceOf(ResponseValidator::class);
    expect($resource->getValidator()->isValid())->toBeFalse();
    expect($resource->getValidator()->getErrors())->not->toBeEmpty();
});

it('skips validation when disabled', function () {
    Config::set('tradingcardapi.validation.enabled', false);

    $client = m::mock(Client::class);

    $invalidResponseData = [
        'completely' => 'invalid',
        'response' => 'structure',
    ];

    $responseBody = json_encode($invalidResponseData);
    $apiResponse = new GuzzleResponse(200, [], $responseBody);

    $client->shouldReceive('request')
        ->once()
        ->andReturn($apiResponse);

    $resource = new TestApiResource($client);

    // Should work fine even with invalid response when validation disabled
    $result = $resource->makeRequest('/v1/cards/123');

    expect($result)->toBeObject();
    expect($result->completely)->toBe('invalid');

    // Should not have created validator
    expect($resource->getValidator())->toBeNull();
});

it('handles empty response body correctly', function () {
    $client = m::mock(Client::class);

    $apiResponse = new GuzzleResponse(204, [], '');

    $client->shouldReceive('request')
        ->once()
        ->andReturn($apiResponse);

    $resource = new TestApiResource($client);

    $result = $resource->makeRequest('/v1/cards/123');

    expect($result)->toBeInstanceOf(stdClass::class);
    expect((array) $result)->toBeEmpty();

    // Should not have validator for empty responses
    expect($resource->getValidator())->toBeNull();
});

it('handles non-JSON API endpoints gracefully', function () {
    $client = m::mock(Client::class);

    $responseBody = json_encode(['some' => 'data']);
    $apiResponse = new GuzzleResponse(200, [], $responseBody);

    $client->shouldReceive('request')
        ->once()
        ->andReturn($apiResponse);

    $resource = new TestApiResource($client);

    // Should work with non-API endpoints (no validation)
    $result = $resource->makeRequest('/oauth/token');

    expect($result)->toBeObject();
    expect($result->some)->toBe('data');

    // Should not have validator for non-API endpoints
    expect($resource->getValidator())->toBeNull();
});
