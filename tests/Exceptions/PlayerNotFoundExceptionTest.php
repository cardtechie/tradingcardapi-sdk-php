<?php

use CardTechie\TradingCardApiSdk\Exceptions\PlayerNotFoundException;

it('creates player not found exception by ID', function () {
    $exception = PlayerNotFoundException::byId('player456', ['team_id' => 'team789']);

    expect($exception->getMessage())->toBe("The player with ID 'player456' was not found");
    expect($exception->getCode())->toBe(404);
    expect($exception->getHttpStatusCode())->toBe(404);
    expect($exception->getApiErrorCode())->toBe('player_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Player Not Found');
    expect($exception->getContext())->toHaveKey('resource_type', 'player');
    expect($exception->getContext())->toHaveKey('resource_id', 'player456');
    expect($exception->getContext())->toHaveKey('team_id', 'team789');
});

it('creates player not found exception by name', function () {
    $exception = PlayerNotFoundException::byName('Michael Jordan', ['season' => '1995-96']);

    expect($exception->getMessage())->toBe("Player 'Michael Jordan' was not found");
    expect($exception->getApiErrorCode())->toBe('player_not_found');
    expect($exception->getApiErrors())->toHaveCount(1);
    expect($exception->getApiErrors()[0]['title'])->toBe('Player Not Found');
    expect($exception->getApiErrors()[0]['detail'])->toBe("No player found with the name 'Michael Jordan'");
    expect($exception->getContext())->toHaveKey('player_name', 'Michael Jordan');
    expect($exception->getContext())->toHaveKey('season', '1995-96');
});

it('inherits from ResourceNotFoundException', function () {
    $exception = PlayerNotFoundException::byId('test123');
    expect($exception)->toBeInstanceOf(\CardTechie\TradingCardApiSdk\Exceptions\ResourceNotFoundException::class);
});
