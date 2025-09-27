<?php

use CardTechie\TradingCardApiSdk\Models\Player as PlayerModel;
use CardTechie\TradingCardApiSdk\Resources\Player;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Pagination\LengthAwarePaginator;
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
        ->with('GET', '/v1/players?', m::type('array'))
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
        ->with('POST', '/v1/players', m::type('array'))
        ->once()
        ->andReturn($playerResponse);

    $player = new Player($client);
    $result = $player->create(['name' => 'New Player', 'position' => 'Center']);

    expect($result)->toBeInstanceOf(PlayerModel::class);
});

it('can get a player by id', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the player get request
    $playerResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'players',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/v1/players/123', m::type('array'))
        ->once()
        ->andReturn($playerResponse);

    $player = new Player($client);
    $result = $player->get('123');

    expect($result)->toBeInstanceOf(PlayerModel::class);
    expect($result->id)->toBe('123');
});

it('can list players with pagination', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the players list request with pagination
    $playersResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            [
                'id' => '1',
                'type' => 'players',
                'attributes' => [
                    'first_name' => 'Player',
                    'last_name' => 'One',
                ],
            ],
            [
                'id' => '2',
                'type' => 'players',
                'attributes' => [
                    'first_name' => 'Player',
                    'last_name' => 'Two',
                ],
            ],
        ],
        'meta' => [
            'pagination' => [
                'total' => 100,
                'per_page' => 50,
                'current_page' => 1,
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/v1/players?limit=50&page=1&pageName=page', m::type('array'))
        ->once()
        ->andReturn($playersResponse);

    $player = new Player($client);
    $result = $player->list();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(100);
    expect($result->perPage())->toBe(50);
    expect($result->currentPage())->toBe(1);
});

it('can update a player', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the player update request
    $playerResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'players',
            'attributes' => [
                'first_name' => 'John',
                'last_name' => 'Smith',
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('PUT', '/v1/players/123', m::type('array'))
        ->once()
        ->andReturn($playerResponse);

    $player = new Player($client);
    $result = $player->update('123', ['last_name' => 'Smith']);

    expect($result)->toBeInstanceOf(PlayerModel::class);
    expect($result->id)->toBe('123');
});

it('can delete a player', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the player delete request
    $deleteResponse = new GuzzleResponse(204, [], '');

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('DELETE', '/v1/players/123', m::type('array'))
        ->once()
        ->andReturn($deleteResponse);

    $player = new Player($client);
    $result = $player->delete('123');

    expect($result)->toBeNull();
});

it('can list deleted players', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the deleted players list request
    $deletedPlayersResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            [
                'id' => '1',
                'type' => 'players',
                'attributes' => [
                    'first_name' => 'Deleted',
                    'last_name' => 'Player',
                ],
            ],
        ],
        'meta' => [
            'pagination' => [
                'total' => 1,
                'per_page' => 50,
                'current_page' => 1,
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/v1/players/deleted', m::type('array'))
        ->once()
        ->andReturn($deletedPlayersResponse);

    $player = new Player($client);
    $result = $player->listDeleted();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->total())->toBe(1);
});

it('can get a deleted player by id', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the deleted player get request
    $deletedPlayerResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'players',
            'attributes' => [
                'first_name' => 'Deleted',
                'last_name' => 'Player',
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('GET', '/v1/players/123/deleted', m::type('array'))
        ->once()
        ->andReturn($deletedPlayerResponse);

    $player = new Player($client);
    $result = $player->deleted('123');

    expect($result)->toBeInstanceOf(PlayerModel::class);
    expect($result->id)->toBe('123');
});

it('can create a player with relationships', function () {
    $client = m::mock(Client::class);

    // Mock the OAuth token request
    $tokenResponse = new GuzzleResponse(200, [], json_encode([
        'access_token' => 'test-token',
        'token_type' => 'Bearer',
    ]));

    // Mock the player creation request with relationships
    $playerResponse = new GuzzleResponse(200, [], json_encode([
        'data' => [
            'id' => '123',
            'type' => 'players',
            'attributes' => [
                'first_name' => 'New',
                'last_name' => 'Player',
            ],
            'relationships' => [
                'parent' => [
                    'data' => [
                        'type' => 'players',
                        'id' => '456',
                    ],
                ],
            ],
        ],
    ]));

    $client->shouldReceive('request')
        ->with('POST', '/oauth/token', m::type('array'))
        ->once()
        ->andReturn($tokenResponse);

    $client->shouldReceive('request')
        ->with('POST', '/v1/players', m::type('array'))
        ->once()
        ->andReturn($playerResponse);

    $player = new Player($client);
    $result = $player->create(
        ['first_name' => 'New', 'last_name' => 'Player'],
        ['parent' => ['data' => ['type' => 'players', 'id' => '456']]]
    );

    expect($result)->toBeInstanceOf(PlayerModel::class);
    expect($result->id)->toBe('123');
});
