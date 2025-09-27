<?php

use CardTechie\TradingCardApiSdk\Models\Player;

it('can be instantiated with attributes', function () {
    $player = new Player(['id' => '123', 'first_name' => 'John', 'last_name' => 'Doe']);

    expect($player)->toBeInstanceOf(Player::class);
    expect($player->id)->toBe('123');
    expect($player->first_name)->toBe('John');
    expect($player->last_name)->toBe('Doe');
});

it('returns full name attribute', function () {
    $player = new Player(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($player->full_name)->toBe('John Doe');
});

it('handles null full name gracefully', function () {
    $player = new Player(['first_name' => null, 'last_name' => 'Doe']);

    expect($player->full_name)->toBe('Doe');
});

it('handles empty names in full name', function () {
    $player = new Player(['first_name' => '', 'last_name' => '']);

    expect($player->full_name)->toBe('');
});

it('returns last name first attribute', function () {
    $player = new Player(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($player->last_name_first)->toBe('Doe, John');
});

it('handles last name first with only first name', function () {
    $player = new Player(['first_name' => 'John', 'last_name' => '']);

    expect($player->last_name_first)->toBe('John');
});

it('handles last name first with only last name', function () {
    $player = new Player(['first_name' => '', 'last_name' => 'Doe']);

    expect($player->last_name_first)->toBe('Doe');
});

it('can check if player is an alias', function () {
    $aliasPlayer = new Player(['id' => '123', 'parent_id' => '456']);
    $parentPlayer = new Player(['id' => '456', 'parent_id' => null]);

    expect($aliasPlayer->isAlias())->toBeTrue();
    expect($parentPlayer->isAlias())->toBeFalse();
});

it('has relationship methods defined', function () {
    $player = new Player();

    expect(method_exists($player, 'getParent'))->toBeTrue();
    expect(method_exists($player, 'getAliases'))->toBeTrue();
    expect(method_exists($player, 'getTeams'))->toBeTrue();
    expect(method_exists($player, 'getPlayerteams'))->toBeTrue();
    expect(method_exists($player, 'getCards'))->toBeTrue();
    expect(method_exists($player, 'hasAliases'))->toBeTrue();
});

it('implements Taxonomy interface', function () {
    $player = new Player;

    expect($player)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Models\Taxonomy::class);
});

it('build method returns the taxonomy object', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '123';
    $data = ['test' => 'data'];

    $result = Player::build($taxonomy, $data);

    expect($result)->toBe($taxonomy);
    expect($result->id)->toBe('123');
});

it('build method sets player relationship when matching data exists', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '123';

    $playerData = new \stdClass;
    $playerData->id = '123';
    $playerData->name = 'John Doe';

    $data = ['player' => [$playerData]];

    $result = Player::build($taxonomy, $data);

    expect($result->relationships['player'])->toBe($playerData);
});

it('build method handles direct player object data', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '123';

    $playerData = new \stdClass;
    $playerData->id = '123';
    $playerData->name = 'John Doe';

    $data = ['player' => $playerData];

    $result = Player::build($taxonomy, $data);

    expect($result->relationships['player'])->toBe($playerData);
});

it('getFromApi method exists and is properly defined', function () {
    // Test that the method exists and has proper structure
    expect(method_exists(Player::class, 'getFromApi'))->toBeTrue();

    $reflection = new ReflectionMethod(Player::class, 'getFromApi');
    expect($reflection->isStatic())->toBeTrue();
    expect($reflection->isPublic())->toBeTrue();

    // Check method parameters
    $parameters = $reflection->getParameters();
    expect($parameters)->toHaveCount(1);
    expect($parameters[0]->getName())->toBe('params');
});

it('can get parent player when player has parent_id', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $parentPlayer = new Player(['id' => '456', 'first_name' => 'Parent', 'last_name' => 'Player']);
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('get')->with('456')->andReturn($parentPlayer);
    
    $player = new Player(['id' => '123', 'parent_id' => '456']);
    $result = $player->getParent();
    
    expect($result)->toBeInstanceOf(Player::class);
    expect($result->id)->toBe('456');
});

it('returns null when player has no parent_id', function () {
    $player = new Player(['id' => '123']);
    $result = $player->getParent();
    
    expect($result)->toBeNull();
});

it('returns null when parent_id is empty', function () {
    $player = new Player(['id' => '123', 'parent_id' => '']);
    $result = $player->getParent();
    
    expect($result)->toBeNull();
});

it('can get aliases for a player', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $alias1 = new Player(['id' => '789', 'parent_id' => '123', 'first_name' => 'Alias', 'last_name' => 'One']);
    $alias2 = new Player(['id' => '790', 'parent_id' => '123', 'first_name' => 'Alias', 'last_name' => 'Two']);
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([$alias1, $alias2]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getAliases();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first()->id)->toBe('789');
});

it('returns empty collection when player has no aliases', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getAliases();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(0);
});

it('can get teams for a player', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerteamResource = \Mockery::mock();
    
    $mockTeam = \Mockery::mock();
    $mockTeam->shouldReceive('team')->andReturn((object)['id' => '111', 'name' => 'Test Team']);
    
    $mockSdk->shouldReceive('playerteam')->andReturn($mockPlayerteamResource);
    $mockPlayerteamResource->shouldReceive('getList')
        ->with(['player_id' => '123', 'include' => 'team'])
        ->andReturn(collect([$mockTeam]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getTeams();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(1);
});

it('can get playerteams for a player', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerteamResource = \Mockery::mock();
    
    $playerteam = (object)['id' => '999', 'player_id' => '123', 'team_id' => '111'];
    
    $mockSdk->shouldReceive('playerteam')->andReturn($mockPlayerteamResource);
    $mockPlayerteamResource->shouldReceive('getList')
        ->with(['player_id' => '123'])
        ->andReturn(collect([$playerteam]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getPlayerteams();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(1);
});

it('can get cards for a player', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockCardResource = \Mockery::mock();
    $mockPaginatedResult = \Mockery::mock();
    
    $card1 = (object)['id' => '555', 'name' => 'Card 1'];
    $card2 = (object)['id' => '556', 'name' => 'Card 2'];
    
    $mockSdk->shouldReceive('card')->andReturn($mockCardResource);
    $mockCardResource->shouldReceive('list')
        ->with(['player_id' => '123', 'limit' => 1000])
        ->andReturn($mockPaginatedResult);
    $mockPaginatedResult->shouldReceive('getCollection')
        ->andReturn(collect([$card1, $card2]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getCards();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(2);
});

it('can check if player has aliases', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $alias = new Player(['id' => '789', 'parent_id' => '123']);
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([$alias]));
    
    $player = new Player(['id' => '123']);
    
    expect($player->hasAliases())->toBeTrue();
});

it('returns false when player has no aliases', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([]));
    
    $player = new Player(['id' => '123']);
    
    expect($player->hasAliases())->toBeFalse();
});

it('handles exception when getting parent player', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('get')
        ->with('456')
        ->andThrow(new \Exception('API Error'));
    
    $player = new Player(['id' => '123', 'parent_id' => '456']);
    $result = $player->getParent();
    
    expect($result)->toBeNull();
});

it('handles exception when getting aliases', function () {
    // Mock the TradingCardApiSdk facade and Log facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    \Mockery::mock('alias:Illuminate\Support\Facades\Log')
        ->shouldReceive('error')
        ->once()
        ->with(\Mockery::type('string'), \Mockery::type('array'));
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->andThrow(new \Exception('API Error'));
    
    $player = new Player(['id' => '123']);
    $result = $player->getAliases();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(0);
});

it('handles exception when getting teams', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerteamResource = \Mockery::mock();
    
    $mockSdk->shouldReceive('playerteam')->andReturn($mockPlayerteamResource);
    $mockPlayerteamResource->shouldReceive('getList')
        ->with(['player_id' => '123', 'include' => 'team'])
        ->andThrow(new \Exception('API Error'));
    
    $player = new Player(['id' => '123']);
    $result = $player->getTeams();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(0);
});

it('handles exception when getting playerteams', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerteamResource = \Mockery::mock();
    
    $mockSdk->shouldReceive('playerteam')->andReturn($mockPlayerteamResource);
    $mockPlayerteamResource->shouldReceive('getList')
        ->with(['player_id' => '123'])
        ->andThrow(new \Exception('API Error'));
    
    $player = new Player(['id' => '123']);
    $result = $player->getPlayerteams();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(0);
});

it('handles exception when getting cards', function () {
    // Mock the TradingCardApiSdk facade and Log facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockCardResource = \Mockery::mock();
    \Mockery::mock('alias:Illuminate\Support\Facades\Log')
        ->shouldReceive('error')
        ->once()
        ->with(\Mockery::type('string'), \Mockery::type('array'));
    
    $mockSdk->shouldReceive('card')->andReturn($mockCardResource);
    $mockCardResource->shouldReceive('list')
        ->with(['player_id' => '123', 'limit' => 1000])
        ->andThrow(new \Exception('API Error'));
    
    $player = new Player(['id' => '123']);
    $result = $player->getCards();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(0);
});

it('filters out invalid aliases correctly', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    // Create a mix of valid and invalid aliases
    $validAlias = new Player(['id' => '789', 'parent_id' => '123']);
    $invalidAlias = new Player(['id' => '790', 'parent_id' => '456']); // Different parent
    $noParentAlias = new Player(['id' => '791']); // No parent_id
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([$validAlias, $invalidAlias, $noParentAlias]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getAliases();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(1); // Only the valid alias should be returned
    expect($result->first()->id)->toBe('789');
});

it('tries multiple filter attempts for aliases', function () {
    // Mock the TradingCardApiSdk facade
    $mockSdk = \Mockery::mock('alias:CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk');
    $mockPlayerResource = \Mockery::mock();
    
    $alias = new Player(['id' => '789', 'parent_id' => '123']);
    
    $mockSdk->shouldReceive('player')->andReturn($mockPlayerResource);
    
    // First attempt fails, second succeeds
    $mockPlayerResource->shouldReceive('getList')
        ->with(['parent_id' => '123'])
        ->andReturn(collect([]));
    $mockPlayerResource->shouldReceive('getList')
        ->with(['filter[parent_id]' => '123'])
        ->andReturn(collect([$alias]));
    
    $player = new Player(['id' => '123']);
    $result = $player->getAliases();
    
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe('789');
});
