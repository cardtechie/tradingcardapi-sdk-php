<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Set;

/**
 * Typed response for the V2 set checklist endpoint (`Set::checklistV2`).
 *
 * Models the JSON:API payload returned by `GET /v2/sets/{id}/checklist`, which
 * differs from the V1 envelope: the cards are the primary `data` collection,
 * the set (and any other requested relationships) live in `included`, and a
 * `meta.pagination` block is present when the request supplied `per_page`.
 */
class ChecklistV2Response
{
    /**
     * @param  array<mixed>  $cards  The primary `data` collection (checklist cards)
     * @param  array<mixed>  $included  The raw `included` resources (set, oncard, attributes, ...)
     * @param  object|null  $set  The included set resource, extracted for convenience, or null
     * @param  int|null  $currentPage  `meta.pagination.current_page`, null when not paginated
     * @param  int|null  $perPage  `meta.pagination.per_page`, null when not paginated
     * @param  int|null  $total  `meta.pagination.total`, null when not paginated
     * @param  int|null  $totalPages  `meta.pagination.total_pages`, null when not paginated
     */
    public function __construct(
        public readonly array $cards,
        public readonly array $included,
        public readonly ?object $set,
        public readonly ?int $currentPage = null,
        public readonly ?int $perPage = null,
        public readonly ?int $total = null,
        public readonly ?int $totalPages = null,
    ) {}

    public static function fromResponse(object $response): self
    {
        $cards = self::toArray($response->data ?? []);
        $included = self::toArray($response->included ?? []);

        $pagination = $response->meta->pagination ?? null;

        return new self(
            cards: $cards,
            included: $included,
            set: self::extractSet($included),
            currentPage: self::intOrNull($pagination->current_page ?? null),
            perPage: self::intOrNull($pagination->per_page ?? null),
            total: self::intOrNull($pagination->total ?? null),
            totalPages: self::intOrNull($pagination->total_pages ?? null),
        );
    }

    /**
     * Pull the set resource out of the `included` collection (the first
     * resource whose JSON:API `type` is `sets`), returning null when absent.
     *
     * @param  array<mixed>  $included
     */
    private static function extractSet(array $included): ?object
    {
        foreach ($included as $item) {
            if (is_object($item) && ($item->type ?? null) === 'sets') {
                return $item;
            }
        }

        return null;
    }

    /**
     * Normalize a stdClass/array payload to a plain array.
     *
     * @return array<mixed>
     */
    private static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    /**
     * Coerce a pagination value to an int, preserving null when absent.
     */
    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
