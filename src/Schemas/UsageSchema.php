<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for `GET /v1/user/usage` responses.
 */
class UsageSchema extends BaseSchema
{
    /**
     * Get validation rules for usage responses.
     *
     * Deliberately does not reuse `getJsonApiRules()`: the usage document ships
     * `data.type` and `data.attributes` but no `data.id`, and the inherited
     * rules declare `data.id` as `required`, which would log a spurious
     * validation warning on every real response.
     *
     * `data.type` is constrained to the literal `usage` — the only value the
     * API's `UsageController` emits — matching how every other schema here
     * pins its own resource type. A mismapped endpoint or an unexpected
     * payload then surfaces as a validation warning instead of passing.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return [
            'data' => 'required|array',
            'data.id' => 'sometimes|string',
            'data.type' => 'required|string|in:usage',
            'data.attributes' => 'required|array',
            'data.attributes.limit' => 'required|integer',
            'data.attributes.remaining' => 'required|integer',
            'data.attributes.resets_at' => 'required|string',
        ];
    }
}
