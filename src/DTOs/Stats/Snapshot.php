<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * One historical snapshot row for an entity type, from /v1/stats/snapshots.
 *
 * Status-posture rule: identical to EntityCount — the API applies the same
 * transform per snapshot row. On a token without `internal`, `read:all-status`
 * or `read:draft` (including this SDK's default `read:published` scope) each row
 * arrives with `total = published` and `draft = 0`; `archived` is always 0.
 * See EntityCount's class docblock for the full statement of the rule.
 *
 * `entity_type` is singular here too (`set`, `card`, `player`, `team`).
 *
 * One extra wrinkle specific to snapshots: `published` is read back from the
 * nightly snapshot job, so for players and teams it can lag the live total. The
 * API floors `draft` at 0 rather than letting a stale row go negative, and a
 * restricted caller sees `total` 0 for those types until the job has first run.
 *
 * @see EntityCount
 * @see https://github.com/cardtechie/tradingcardapi-api/issues/2435
 */
class Snapshot
{
    public function __construct(
        public readonly string $date,
        public readonly string $entityType,
        /** Rows the calling token can read at that date. Equals $published unless the token sees draft. */
        public readonly int $total,
        public readonly int $published,
        /** Always 0 unless the calling token holds internal, read:all-status or read:draft. */
        public readonly int $draft,
        /** Always 0 — the API sends the key but does not populate it. */
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
