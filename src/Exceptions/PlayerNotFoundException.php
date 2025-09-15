<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a specific player is not found
 */
class PlayerNotFoundException extends ResourceNotFoundException
{
    /**
     * Create exception for player not found by ID
     */
    public static function byId(string $playerId, array $context = []): self
    {
        return new self(
            "The player with ID '{$playerId}' was not found",
            404,
            null,
            'player_not_found',
            [['title' => 'Player Not Found', 'detail' => "The player with ID '{$playerId}' was not found"]],
            array_merge($context, ['resource_type' => 'player', 'resource_id' => $playerId])
        );
    }

    /**
     * Create exception for player not found by name
     */
    public static function byName(string $playerName, array $context = []): self
    {
        return new self(
            "Player '{$playerName}' was not found",
            404,
            null,
            'player_not_found',
            [[
                'title' => 'Player Not Found',
                'detail' => "No player found with the name '{$playerName}'",
            ]],
            array_merge($context, ['player_name' => $playerName])
        );
    }
}
