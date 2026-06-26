<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * A single actionable set from the workflow dashboard / review queue.
 *
 * Wraps one JSON:API resource object; `attributes` is exposed as the
 * decoded object so callers keep access to the full attribute set without
 * the DTO having to enumerate every workflow attribute the API may add.
 */
class ActionableSet
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly object $attributes,
    ) {}

    public static function fromObject(object $data): self
    {
        return new self(
            id: (string) ($data->id ?? ''),
            type: $data->type ?? 'sets',
            attributes: $data->attributes ?? (object) [],
        );
    }
}
