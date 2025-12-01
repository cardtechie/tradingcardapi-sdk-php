<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

class GrowthResponse
{
    /**
     * @param  array<GrowthMetric>  $metrics
     */
    public function __construct(
        public readonly array $metrics,
        public readonly string $period,
    ) {}

    public static function fromResponse(object $response): self
    {
        $metrics = [];
        $attributes = $response->data->attributes ?? (object) [];
        $data = $attributes->metrics ?? [];

        foreach ($data as $item) {
            $metrics[] = GrowthMetric::fromObject($item);
        }

        return new self(
            metrics: $metrics,
            period: $attributes->period ?? '',
        );
    }

    public function getByEntityType(string $entityType): ?GrowthMetric
    {
        foreach ($this->metrics as $metric) {
            if ($metric->entityType === $entityType) {
                return $metric;
            }
        }

        return null;
    }
}
