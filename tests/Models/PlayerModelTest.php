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
    $player = new Player;

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
