<?php

namespace CardTechie\TradingCardApiSdk\Exceptions;

/**
 * Exception thrown when a specific set is not found
 */
class SetNotFoundException extends ResourceNotFoundException
{
    /**
     * Create exception for set not found by ID
     *
     * @param  array<string, mixed>  $context
     */
    public static function byId(string $setId, array $context = []): self
    {
        return new self(
            "The set with ID '{$setId}' was not found",
            404,
            null,
            'set_not_found',
            [['title' => 'Set Not Found', 'detail' => "The set with ID '{$setId}' was not found"]],
            array_merge($context, ['resource_type' => 'set', 'resource_id' => $setId])
        );
    }

    /**
     * Create exception for set not found by name
     *
     * @param  array<string, mixed>  $context
     */
    public static function byName(string $setName, array $context = []): self
    {
        return new self(
            "Set '{$setName}' was not found",
            404,
            null,
            'set_not_found',
            [[
                'title' => 'Set Not Found',
                'detail' => "No set found with the name '{$setName}'",
            ]],
            array_merge($context, ['set_name' => $setName])
        );
    }
}
