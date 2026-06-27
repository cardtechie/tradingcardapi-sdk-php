<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * Typed response for the per-set workflow todos endpoint
 * (`Workflow::getSetTodos`).
 *
 * Models the `todos` collection returned by
 * `GET /internal/workflow/sets/{id}/todos`.
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
        $todos = [];
        foreach ($response->todos ?? [] as $item) {
            $todos[] = SetTodo::fromObject($item);
        }

        return new self(todos: $todos);
    }
}
