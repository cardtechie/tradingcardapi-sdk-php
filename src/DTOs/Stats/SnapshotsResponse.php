<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

class SnapshotsResponse
{
    /**
     * @param  array<Snapshot>  $snapshots
     */
    public function __construct(
        public readonly array $snapshots,
        public readonly ?string $entityType = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
    ) {}

    public static function fromResponse(object $response): self
    {
        $snapshots = [];
        $attributes = $response->data->attributes ?? (object) [];
        $data = $attributes->snapshots ?? [];

        foreach ($data as $item) {
            $snapshots[] = Snapshot::fromObject($item);
        }

        return new self(
            snapshots: $snapshots,
            entityType: $attributes->entity_type ?? null,
            from: $attributes->from ?? null,
            to: $attributes->to ?? null,
        );
    }
}
