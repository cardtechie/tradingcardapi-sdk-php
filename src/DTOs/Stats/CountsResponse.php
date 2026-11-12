<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * The /v1/stats/counts payload: one EntityCount per entity type.
 *
 * Every figure carried by the EntityCount rows below is relative to the calling
 * token's status posture — on the SDK's default `read:published` scope `total`
 * equals `published` and `draft` is 0. See EntityCount for the full rule.
 *
 * @see EntityCount
 */
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
     *
     * $entityType is the API's singular form — `set`, `card`, `player`, `team` —
     * not the plural used by the /v1/stats/{type} path segment. An unknown type
     * returns null.
     *
     * The returned figures are posture-relative; see EntityCount.
     *
     * @see EntityCount
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
