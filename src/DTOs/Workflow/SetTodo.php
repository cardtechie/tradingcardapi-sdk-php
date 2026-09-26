<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * A single workflow todo (step) for a set.
 *
 * `GET /internal/sets/{set}/todos` serializes through Fractal's
 * `JsonApiSerializer`, so each element is a resource object with the real
 * fields under `attributes`. {@see fromObject()} reads `attributes` when
 * present and falls back to the flat shape otherwise.
 */
class SetTodo
{
    public function __construct(
        public readonly string $id,
        public readonly string $step,
        public readonly string $status,
        public readonly ?int $sortOrder = null,
        public readonly ?string $startedAt = null,
        public readonly ?string $completedAt = null,
    ) {}

    public static function fromObject(object $data): self
    {
        $source = isset($data->attributes) && is_object($data->attributes)
            ? $data->attributes
            : $data;

        return new self(
            id: (string) ($data->id ?? ''),
            step: $source->step ?? '',
            status: $source->status ?? '',
            sortOrder: isset($source->sort_order) ? (int) $source->sort_order : null,
            startedAt: $source->started_at ?? null,
            completedAt: $source->completed_at ?? null,
        );
    }
}
