<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

class GrowthMetric
{
    public function __construct(
        public readonly string $entityType,
        public readonly int $current,
        public readonly int $previous,
        public readonly int $change,
        public readonly float $percentageChange,
    ) {}

    public static function fromObject(object $data): self
    {
        return new self(
            entityType: $data->entity_type ?? '',
            current: $data->current ?? 0,
            previous: $data->previous ?? 0,
            change: $data->change ?? 0,
            percentageChange: $data->percentage_change ?? 0.0,
        );
    }
}
