<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * A single workflow todo (step) for a set.
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
        return new self(
            id: (string) ($data->id ?? ''),
            step: $data->step ?? '',
            status: $data->status ?? '',
            sortOrder: $data->sort_order ?? null,
            startedAt: $data->started_at ?? null,
            completedAt: $data->completed_at ?? null,
        );
    }
}
