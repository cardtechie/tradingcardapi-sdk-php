<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Stats;

/**
 * Current counts for one entity type, as reported by /v1/stats/counts.
 *
 * ## Every figure here is relative to the calling token's status posture
 *
 * This is the canonical statement of the rule; the other DTOs in this namespace
 * cross-reference it. Since cardtechie/tradingcardapi-api#2435 the API reports
 * counts describing what the calling token can actually read, not what exists
 * in the catalog.
 *
 * A token holding `internal`, `read:all-status` or `read:draft` receives the
 * full split:
 *
 *     total = every row,  published = published rows,  draft = total - published
 *
 * Every other token — including this SDK's default `read:published` scope, set
 * in `config/tradingcardapi.php` — receives a published-only view:
 *
 *     total = published,  draft = 0
 *
 * So on the default configuration `$total` is NOT a catalog total: it equals
 * `$published`, and `$draft` is permanently `0`. Nothing in the payload signals
 * which of the two views you received — the keys are identical either way — so
 * a consumer that needs the distinction must know its own token's scope. Read
 * `$published` when you mean "published"; do not read `$total` and assume it
 * counts drafts too.
 *
 * `$archived` is always `0`: the API sends the key for shape compatibility but
 * does not currently populate it under either posture.
 *
 * The fields remain optional at the DTO level (see fromObject()'s `?? 0`
 * defaults) so that an API build which drops the keys outright, rather than
 * collapsing them, still deserialises.
 *
 * ## entity_type is singular
 *
 * The API emits singular values here — `set`, `card`, `player`, `team` — while
 * the `/v1/stats/{type}` path segment is plural (`sets`, `cards`, ...). Pass
 * the singular form to CountsResponse::getByEntityType().
 *
 * @see https://github.com/cardtechie/tradingcardapi-api/issues/2435
 */
class EntityCount
{
    public function __construct(
        public readonly string $entityType,
        /** Rows the calling token can read. Equals $published unless the token sees draft. */
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
            entityType: $data->entity_type ?? '',
            total: $data->total ?? 0,
            published: $data->published ?? 0,
            draft: $data->draft ?? 0,
            archived: $data->archived ?? 0,
        );
    }
}
