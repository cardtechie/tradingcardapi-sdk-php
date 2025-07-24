<?php

use CardTechie\TradingCardApiSdk\Models\Player as PlayerModel;
use CardTechie\TradingCardApiSdk\Resources\Player;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Collection;
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
    $player = new Player($client);

    expect($player)->toBeInstanceOf(Player::class);
});

it('can get list of players', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the players list request
    $playersResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            [
                'id' => '1',
                'type' => 'players',
                'attributes' => [
                    'name' => 'Player One',
                    'position' => 'Forward',
                ],
            ],
            [
                'id' => '2',
                'type' => 'players',
                'attributes' => [
                    'name' => 'Player Two',
                    'position' => 'Guard',
                ],
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/players?', m::type('array'))
        ->once()
        ->andReturn($playersResponse);

    $player = new Player($client);
    $result = $player->getList();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
});

it('can create a player', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the player creation request
    $playerResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'players',
            'attributes' => [
                'name' => 'New Player',
                'position' => 'Center',
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('POST', '/players', m::type('array'))
        ->once()
        ->andReturn($playerResponse);

    $player = new Player($client);
    $result = $player->create(['name' => 'New Player', 'position' => 'Center']);

    expect($result)->toBeInstanceOf(PlayerModel::class);
});
