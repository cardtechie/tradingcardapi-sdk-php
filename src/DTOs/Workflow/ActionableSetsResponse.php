<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * Typed response for the workflow actionable-sets / review-queue endpoints
 * (`Workflow::actionableSets`, `Workflow::getReviewQueue`).
 *
 * Models the `data` collection returned by `GET /internal/actionable-sets`
 * along with its `meta` block. The API serves flat rows rather than JSON:API
 * resource objects; {@see ActionableSet::fromObject()} handles both shapes.
 */
class ActionableSetsResponse
{
    /**
     * @param  array<ActionableSet>  $sets
     */
    public function __construct(
        public readonly array $sets,
        public readonly ?ActionableSetsMeta $meta = null,
    ) {}

    public static function fromResponse(object $response): self
    {
        $sets = [];
        foreach ($response->data ?? [] as $item) {
            $sets[] = ActionableSet::fromObject($item);
        }

        $meta = isset($response->meta) && is_object($response->meta)
            ? ActionableSetsMeta::fromObject($response->meta)
            : null;

        return new self(sets: $sets, meta: $meta);
    }
}
