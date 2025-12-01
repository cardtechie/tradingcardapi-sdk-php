<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

class Snapshot
{
    public function __construct(
        public readonly string $date,
        public readonly string $entityType,
        public readonly int $total,
        public readonly int $published,
        public readonly int $draft,
        public readonly int $archived,
    ) {}

    public static function fromObject(object $data): self
    {
        return new self(
            date: $data->date ?? '',
            entityType: $data->entity_type ?? '',
            total: $data->total ?? 0,
            published: $data->published ?? 0,
            draft: $data->draft ?? 0,
            archived: $data->archived ?? 0,
        );
    }
}
