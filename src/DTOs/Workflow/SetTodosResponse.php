<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * Typed response for the per-set workflow todos endpoint
 * (`Workflow::getSetTodos`).
 *
 * Models the JSON:API `data` collection returned by
 * `GET /internal/sets/{set}/todos`, falling back to the legacy flat `todos`
 * key so both shapes decode.
 */
class SetTodosResponse
{
    /**
     * @param  array<SetTodo>  $todos
     */
    public function __construct(
        public readonly array $todos,
    ) {}

    public static function fromResponse(object $response): self
    {
        $items = $response->data ?? $response->todos ?? [];

        $todos = [];
        foreach ($items as $item) {
            $todos[] = SetTodo::fromObject($item);
        }

        return new self(todos: $todos);
    }
}
