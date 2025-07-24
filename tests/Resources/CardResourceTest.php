<?php

use CardTechie\TradingCardApiSdk\Resources\Card;
use CardTechie\TradingCardApiSdk\Models\Card as CardModel;
use CardTechie\TradingCardApiSdk\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery as m;

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

it('can be instantiated with client', function () {
    $client = m::mock(Client::class);
    $card = new Card($client);
    
    expect($card)->toBeInstanceOf(Card::class);
});

it('can create a card with attributes', function () {
    $client = m::mock(Client::class);
    
    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer'
    ]));
    
    // Mock the card creation request
    $cardResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'description' => 'A test card'
            ]
        ]
    ]));
    
    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);
        
    $client->shouldReceive('request')
        ->with('POST', '/cards', m::type('array'))
        ->once()
        ->andReturn($cardResponse);
    
    $card = new Card($client);
    $result = $card->create(['name' => 'Test Card', 'description' => 'A test card']);
    
    expect($result)->toBeInstanceOf(CardModel::class);
});

it('can get a card by id', function () {
    $client = m::mock(Client::class);
    
    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer'
    ]));
    
    // Mock the card get request
    $cardResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Test Card',
                'description' => 'A test card'
            ]
        ]
    ]));
    
    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);
        
    $client->shouldReceive('request')
        ->with('GET', '/cards/123?include=', m::type('array'))
        ->once()
        ->andReturn($cardResponse);
    
    $card = new Card($client);
    $result = $card->get('123');
    
    expect($result)->toBeInstanceOf(CardModel::class);
});

it('can update a card', function () {
    $client = m::mock(Client::class);
    
    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer'
    ]));
    
    // Mock the card update request
    $cardResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'cards',
            'attributes' => [
                'name' => 'Updated Card',
                'description' => 'An updated card'
            ]
        ]
    ]));
    
    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);
        
    $client->shouldReceive('request')
        ->with('PUT', '/cards/123', m::type('array'))
        ->once()
        ->andReturn($cardResponse);
    
    $card = new Card($client);
    $result = $card->update('123', ['name' => 'Updated Card']);
    
    expect($result)->toBeInstanceOf(CardModel::class);
});

it('can delete a card', function () {
    $client = m::mock(Client::class);
    
    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer'
    ]));
    
    // Mock the card delete request
    $deleteResponse = new GuzzleResponse(204, [], '');
    
    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);
        
    $client->shouldReceive('request')
        ->with('DELETE', '/cards/123', m::type('array'))
        ->once()
        ->andReturn($deleteResponse);
    
    $card = new Card($client);
    $card->delete('123');
    
    expect(true)->toBeTrue(); // If we get here without exception, the test passed
});
