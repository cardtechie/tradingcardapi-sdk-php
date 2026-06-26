<?php

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * Typed response for the workflow actionable-sets / review-queue endpoints
 * (`Workflow::actionableSets`, `Workflow::getReviewQueue`).
 *
 * Models the JSON:API `data` collection returned by
 * `GET /internal/workflow/actionable-sets`.
 */
class ActionableSetsResponse
{
    /**
     * @param  array<ActionableSet>  $sets
     */
    public function __construct(
        public readonly array $sets,
    ) {}

    public static function fromResponse(object $response): self
    {
        $sets = [];
        foreach ($response->data ?? [] as $item) {
            $sets[] = ActionableSet::fromObject($item);
        }

        return new self(sets: $sets);
    }
}
