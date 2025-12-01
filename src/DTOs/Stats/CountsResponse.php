<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

class CountsResponse
{
    /**
     * @param  array<EntityCount>  $counts
     */
    public function __construct(
        public readonly array $counts,
    ) {}

    /**
     * Lazy-loaded index for O(1) lookups by entity type.
     *
     * @var array<string, EntityCount>|null
     */
    private ?array $entityTypeIndex = null;

    public static function fromResponse(object $response): self
    {
        $counts = [];
        $data = $response->data->attributes->counts ?? [];

        foreach ($data as $item) {
            $counts[] = EntityCount::fromObject($item);
        }

        return new self(counts: $counts);
    }

    /**
     * Get entity count by type with O(1) indexed lookup.
     */
    public function getByEntityType(string $entityType): ?EntityCount
    {
        // Build index on first access for lazy loading
        if ($this->entityTypeIndex === null) {
            $this->entityTypeIndex = [];
            foreach ($this->counts as $count) {
                $this->entityTypeIndex[$count->entityType] = $count;
            }
        }

        return $this->entityTypeIndex[$entityType] ?? null;
    }
}
