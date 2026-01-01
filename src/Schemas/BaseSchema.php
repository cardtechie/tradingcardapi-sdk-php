<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Base class for API response schemas
 */
abstract class BaseSchema
{
    /**
     * Get validation rules for this schema
     */
    abstract public function getRules(): array;

    /**
     * Get common JSON:API structure rules
     */
    protected function getJsonApiRules(): array
    {
        return [
            'data' => 'required|array',
            'data.id' => 'required|string',
            'data.type' => 'required|string',
            'data.attributes' => 'required|array',
        ];
    }

    /**
     * Get common JSON:API collection structure rules
     */
    protected function getJsonApiCollectionRules(): array
    {
        return [
            'data' => 'required|array',
            'data.*.id' => 'sometimes|required|string',
            'data.*.type' => 'sometimes|required|string',
            'data.*.attributes' => 'sometimes|required|array',
        ];
    }

    /**
     * Get optional meta and links rules
     */
    protected function getMetaLinksRules(): array
    {
        return [
            'meta' => 'sometimes|array',
            'links' => 'sometimes|array',
            'included' => 'sometimes|array',
            'included.*.id' => 'required_with:included|string',
            'included.*.type' => 'required_with:included|string',
            'included.*.attributes' => 'required_with:included|array',
        ];
    }

    /**
     * Get rules for simple object responses (non-JSON:API)
     */
    protected function getSimpleObjectRules(): array
    {
        return [
            'id' => 'sometimes|string',
        ];
    }

    /**
     * Get rules for collection responses (non-JSON:API)
     */
    protected function getSimpleCollectionRules(): array
    {
        return [
            '*' => 'array',
            '*.id' => 'sometimes|string',
        ];
    }

    /**
     * Merge multiple rule arrays
     */
    protected function mergeRules(array ...$ruleArrays): array
    {
        return array_merge([], ...$ruleArrays);
    }
}
