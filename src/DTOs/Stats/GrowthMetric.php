<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * Growth figures for one entity type over a period, from /v1/stats/growth.
 *
 * Status-posture rule, in its sharpest form. The four metric keys below are
 * unchanged by cardtechie/tradingcardapi-api#2435, but *which underlying series
 * feeds them* moves with the calling token's posture: a token holding
 * `internal`, `read:all-status` or `read:draft` gets growth computed off the
 * total series, and every other token — including this SDK's default
 * `read:published` scope — gets it computed off the published series.
 *
 * Nothing in the payload signals the switch, so a restricted caller's growth
 * numbers are not comparable with a privileged caller's even though the two
 * responses are shape-identical. See EntityCount for the full rule.
 *
 * The published series fails closed: when no snapshot in the window carries a
 * published count, a restricted caller receives zeros rather than a silent
 * fallback to the totals it is not allowed to see. Zeros here therefore mean
 * "no published data for this window", not necessarily "no growth".
 *
 * `entity_type` is singular (`set`, `card`, `player`, `team`).
 *
 * @see EntityCount
 * @see https://github.com/cardtechie/tradingcardapi-api/issues/2435
 */
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
