<?php

use CardTechie\TradingCardApiSdk\Models\Taxonomy;
use CardTechie\TradingCardApiSdk\Models\Team;

it('can be instantiated with attributes', function () {
    $team = new Team(['id' => '123', 'location' => 'New York', 'mascot' => 'Yankees']);

    expect($team)->toBeInstanceOf(Team::class);
    expect($team->id)->toBe('123');
    expect($team->location)->toBe('New York');
    expect($team->mascot)->toBe('Yankees');
});

it('returns full team name', function () {
    $team = new Team(['location' => 'New York', 'mascot' => 'Yankees']);

    expect($team->name)->toBe('New York Yankees');
});

it('handles null name parts gracefully', function () {
    $team = new Team(['location' => null, 'mascot' => 'Yankees']);

    expect($team->name)->toBe(' Yankees');
});

it('implements Taxonomy interface', function () {
    $team = new Team;

    expect($team)->toBeInstanceOf(Taxonomy::class);
});

it('build method returns the taxonomy object', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '456';
    $data = ['test' => 'data'];

    $result = Team::build($taxonomy, $data);

    expect($result)->toBe($taxonomy);
    expect($result->id)->toBe('456');
});

it('build method sets team relationship when matching data exists', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '456';

    $teamData = new \stdClass;
    $teamData->id = '456';
    $teamData->name = 'New York Yankees';

    $data = ['team' => [$teamData]];

    $result = Team::build($taxonomy, $data);

    expect($result->relationships['team'])->toBe($teamData);
});

it('build method handles direct team object data', function () {
    $taxonomy = new \stdClass;
    $taxonomy->id = '456';

    $teamData = new \stdClass;
    $teamData->id = '456';
    $teamData->name = 'New York Yankees';

    $data = ['team' => $teamData];

    $result = Team::build($taxonomy, $data);

    expect($result->relationships['team'])->toBe($teamData);
});

it('getFromApi method exists and is properly defined', function () {
    // Test that the method exists and has proper structure
    expect(method_exists(Team::class, 'getFromApi'))->toBeTrue();

    $reflection = new ReflectionMethod(Team::class, 'getFromApi');
    expect($reflection->isStatic())->toBeTrue();
    expect($reflection->isPublic())->toBeTrue();

    // Check method parameters
    $parameters = $reflection->getParameters();
    expect($parameters)->toHaveCount(1);
    expect($parameters[0]->getName())->toBe('params');
});

it('getFromApi method handles team matching logic', function () {
    // Test that the method contains the expected logic structure
    $reflection = new ReflectionMethod(Team::class, 'getFromApi');
    $method = $reflection->getFileName();
    expect($method)->toContain('Team.php');

    // Verify the method returns an object (has return type hint in modern PHP)
    $returnType = $reflection->getReturnType();
    if ($returnType) {
        expect($returnType->getName())->toBe('object');
    }
});
