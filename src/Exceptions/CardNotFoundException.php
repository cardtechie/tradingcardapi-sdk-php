<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a specific card is not found
 */
class CardNotFoundException extends ResourceNotFoundException
{
    /**
     * Create exception for card not found by ID
     *
     * @param  array<string, mixed>  $context
     */
    public static function byId(string $cardId, array $context = []): self
    {
        return new self(
            "The card with ID '{$cardId}' was not found",
            404,
            null,
            'card_not_found',
            [['title' => 'Card Not Found', 'detail' => "The card with ID '{$cardId}' was not found"]],
            array_merge($context, ['resource_type' => 'card', 'resource_id' => $cardId])
        );
    }

    /**
     * Create exception for card not found by criteria
     *
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $context
     */
    public static function byCriteria(array $criteria, array $context = []): self
    {
        $criteriaString = implode(', ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($criteria), $criteria));

        return new self(
            "No card found matching criteria: {$criteriaString}",
            404,
            null,
            'card_not_found',
            [[
                'title' => 'Card Not Found',
                'detail' => 'No card found matching the specified criteria',
            ]],
            array_merge($context, ['criteria' => $criteria])
        );
    }
}
