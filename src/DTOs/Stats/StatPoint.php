<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * A single point in a stats time-series (one date bucket), from /v1/stats/{type}.
 *
 * Status-posture rule: the rows behind this series are filtered by the calling
 * token's posture for the two set-derived types only. Since
 * cardtechie/tradingcardapi-api#2435 `sets` and `cards` are joined against the
 * set publication status and restricted to the statuses the token may see, so a
 * token without `internal`, `read:all-status` or `read:draft` — including this
 * SDK's default `read:published` scope — sees a published-only series and both
 * `$count` and the running `$total` shrink accordingly.
 *
 * Every other type this endpoint accepts is taxonomy (`players`, `teams`,
 * `attributes`, `brands`, `genres`, `manufacturers`, `years`) and is
 * deliberately ungated: those tables carry no publication status of their own.
 * That is a settled API decision, not a gap.
 *
 * Note the endpoint's path segment is plural, unlike the singular `entity_type`
 * values carried in the counts, snapshots and growth payloads.
 *
 * @see EntityCount
 * @see https://github.com/cardtechie/tradingcardapi-api/issues/2435
 */
class StatPoint
{
    public function __construct(
        public readonly string $date,
        /** New rows in this bucket that the calling token can read. */
        public readonly int $count,
        /** Running total at this bucket, over the rows the calling token can read. */
        public readonly int $total,
    ) {}

    public static function fromObject(object $data): self
    {
        return new self(
            date: $data->date ?? '',
            count: $data->count ?? 0,
            total: $data->total ?? 0,
        );
    }
}
