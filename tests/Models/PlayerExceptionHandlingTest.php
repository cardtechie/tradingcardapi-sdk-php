<?php

declare(strict_types=1);

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\Models\Player;
use CardTechie\TradingCardApiSdk\Resources\Card as CardResource;
use CardTechie\TradingCardApiSdk\Resources\Player as PlayerResource;
use CardTechie\TradingCardApiSdk\Resources\Playerteam as PlayerteamResource;
use CardTechie\TradingCardApiSdk\TradingCardApi;
use Mockery as m;

afterEach(function () {
    // swapResourceToThrow() calls TradingCardApiSdk::swap(), which stores the
    // mock as the facade's resolved instance globally. Clear it so subsequent
    // tests re-resolve the real TradingCardApi from the container instead of
    // inheriting this file's (now closed) mock — otherwise the suite becomes
    // order-dependent.
    TradingCardApiSdk::clearResolvedInstance(TradingCardApi::class);

    m::close();
});

/**
 * Swap the TradingCardApi instance behind the facade so a given resource
 * accessor (player/playerteam/card) returns a resource mock whose request
 * method throws. This proves the Player getter lets the exception propagate
 * rather than masking it as an empty collection.
 *
 * The resource mock is typed against the concrete Resource class so it
 * satisfies the accessor's declared return type (e.g. TradingCardApi::player()
 * is typed `: Resources\Player`).
 *
 * @param  string  $accessor  TradingCardApi accessor to stub (e.g. 'player', 'playerteam', 'card')
 * @param  class-string  $resourceClass  Concrete Resource class the mock is typed against
 * @param  string  $method  Resource method that should throw (e.g. 'all')
 * @param  Throwable  $exception  Exception the stubbed method throws
 */
function swapResourceToThrow(string $accessor, string $resourceClass, string $method, Throwable $exception): void
{
    $resource = m::mock($resourceClass);
    $resource->shouldReceive($method)->andThrow($exception);

    $api = m::mock(TradingCardApi::class);
    $api->shouldReceive($accessor)->andReturn($resource);

    TradingCardApiSdk::swap($api);
}

it('getAliases propagates exceptions instead of returning an empty collection', function () {
    swapResourceToThrow('player', PlayerResource::class, 'all', new RuntimeException('network down'));

    $player = new Player(['id' => 'player-1']);

    expect(fn () => $player->getAliases())
        ->toThrow(RuntimeException::class, 'network down');
});

it('getTeams propagates exceptions instead of returning an empty collection', function () {
    swapResourceToThrow('playerteam', PlayerteamResource::class, 'all', new RuntimeException('network down'));

    $player = new Player(['id' => 'player-1']);

    expect(fn () => $player->getTeams())
        ->toThrow(RuntimeException::class, 'network down');
});

it('getPlayerteams propagates exceptions instead of returning an empty collection', function () {
    swapResourceToThrow('playerteam', PlayerteamResource::class, 'all', new RuntimeException('network down'));

    $player = new Player(['id' => 'player-1']);

    expect(fn () => $player->getPlayerteams())
        ->toThrow(RuntimeException::class, 'network down');
});

it('getCards propagates exceptions instead of returning an empty collection', function () {
    swapResourceToThrow('card', CardResource::class, 'list', new RuntimeException('network down'));

    $player = new Player(['id' => 'player-1']);

    expect(fn () => $player->getCards())
        ->toThrow(RuntimeException::class, 'network down');
});
