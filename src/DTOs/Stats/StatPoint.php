<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * A single point in a stats time-series (one date bucket).
 */
class StatPoint
{
    public function __construct(
        public readonly string $date,
        public readonly int $count,
        public readonly int $total,
    ) {}

    public static function fromObject(object $data): self
    {
        return new self(
            date: $data->date ?? '',
            count: $data->count ?? 0,
            total: $data->total ?? 0,
        );
    }
}
