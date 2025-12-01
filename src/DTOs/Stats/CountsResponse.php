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

    public static function fromResponse(object $response): self
    {
        $counts = [];
        $data = $response->data->attributes->counts ?? [];

        foreach ($data as $item) {
            $counts[] = EntityCount::fromObject($item);
        }

        return new self(counts: $counts);
    }

    public function getByEntityType(string $entityType): ?EntityCount
    {
        foreach ($this->counts as $count) {
            if ($count->entityType === $entityType) {
                return $count;
            }
        }

        return null;
    }
}
