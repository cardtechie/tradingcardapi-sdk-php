<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * The `meta` block returned alongside `GET /internal/actionable-sets`.
 *
 * `fullTotal` is the only way to tell that the returned page was truncated:
 * `total` counts the rows in this response, `fullTotal` counts every matching
 * row. `step`, `status`, and `priorityFilter` echo the filters the API applied.
 *
 * Every field is nullable — the API may omit the block entirely, or return it
 * partially populated.
 */
class ActionableSetsMeta
{
    public function __construct(
        public readonly ?int $total = null,
        public readonly ?int $fullTotal = null,
        public readonly ?string $step = null,
        public readonly ?string $status = null,
        public readonly ?string $priorityFilter = null,
    ) {}

    public static function fromObject(object $meta): self
    {
        return new self(
            total: isset($meta->total) ? (int) $meta->total : null,
            fullTotal: isset($meta->full_total) ? (int) $meta->full_total : null,
            step: isset($meta->step) ? (string) $meta->step : null,
            status: isset($meta->status) ? (string) $meta->status : null,
            priorityFilter: isset($meta->priority_filter) ? (string) $meta->priority_filter : null,
        );
    }
}
