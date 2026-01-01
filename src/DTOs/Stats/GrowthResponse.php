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

    /**
     * Lazy-loaded index for O(1) lookups by entity type.
     *
     * @var array<string, GrowthMetric>|null
     */
    private ?array $entityTypeIndex = null;

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

    /**
     * Get growth metric by entity type with O(1) indexed lookup.
     */
    public function getByEntityType(string $entityType): ?GrowthMetric
    {
        // Build index on first access for lazy loading
        if ($this->entityTypeIndex === null) {
            $this->entityTypeIndex = [];
            foreach ($this->metrics as $metric) {
                $this->entityTypeIndex[$metric->entityType] = $metric;
            }
        }

        return $this->entityTypeIndex[$entityType] ?? null;
    }
}
