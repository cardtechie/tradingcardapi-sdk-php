<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Set;

/**
 * Typed response for the set checklist endpoint (`Set::checklist`).
 *
 * Models the `data` payload returned by `GET /v1/sets/{id}/checklist`:
 * the cards present on the checklist, the cards still missing from it,
 * and (when the API reports it) the total card count.
 */
class ChecklistResponse
{
    /**
     * @param  array<mixed>  $checklist  Cards present on the checklist
     * @param  array<mixed>  $missing  Cards still missing from the checklist
     */
    public function __construct(
        public readonly array $checklist,
        public readonly array $missing,
        public readonly ?int $totalCards = null,
    ) {}

    public static function fromResponse(object $response): self
    {
        $data = $response->data ?? (object) [];

        return new self(
            checklist: self::toArray($data->checklist ?? []),
            missing: self::toArray($data->missing ?? []),
            totalCards: $data->total_cards ?? null,
        );
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
}
