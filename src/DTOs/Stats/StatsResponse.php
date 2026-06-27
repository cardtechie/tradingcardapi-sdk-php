<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * Typed response for the per-model stats time-series endpoint (`Stats::get`).
 *
 * Models the `data.attributes` payload returned by `/v1/stats/{type}`:
 * a model name, the bucketing unit, a bucket count, and the time-series
 * points themselves.
 */
class StatsResponse
{
    /**
     * @param  array<StatPoint>  $stats
     */
    public function __construct(
        public readonly string $model,
        public readonly string $unit,
        public readonly int $count,
        public readonly array $stats,
    ) {}

    public static function fromResponse(object $response): self
    {
        $attributes = $response->data->attributes ?? (object) [];

        $stats = [];
        foreach ($attributes->stats ?? [] as $item) {
            $stats[] = StatPoint::fromObject($item);
        }

        return new self(
            model: $attributes->model ?? '',
            unit: $attributes->unit ?? '',
            count: $attributes->count ?? 0,
            stats: $stats,
        );
    }
}
