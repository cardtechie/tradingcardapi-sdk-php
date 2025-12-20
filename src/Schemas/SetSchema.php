<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Set API responses
 */
class SetSchema extends BaseSchema
{
    /**
     * Get validation rules for Set responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getSetSpecificRules()
        );
    }

    /**
     * Get Set-specific validation rules
     */
    private function getSetSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:sets,set',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.release_date' => 'sometimes|string|nullable',
            'data.attributes.card_count' => 'sometimes|integer|nullable',
            'data.attributes.series' => 'sometimes|string|nullable',
            'data.attributes.brand' => 'sometimes|string|nullable',
            'data.attributes.manufacturer' => 'sometimes|string|nullable',
            'data.attributes.year' => 'sometimes|integer|nullable',
            'data.attributes.prefix' => 'sometimes|string|nullable',
            'data.attributes.image' => 'sometimes|string|nullable',
            'data.attributes.image_thumbnail' => 'sometimes|string|nullable',
            'data.attributes.is_subset' => 'sometimes|boolean|nullable',
            'data.attributes.is_variation' => 'sometimes|boolean|nullable',
            'data.attributes.parent_set_id' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Set collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getSetCollectionSpecificRules()
        );
    }

    /**
     * Get Set collection-specific validation rules
     */
    private function getSetCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:sets,set',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.release_date' => 'sometimes|string|nullable',
            'data.*.attributes.card_count' => 'sometimes|integer|nullable',
            'data.*.attributes.series' => 'sometimes|string|nullable',
            'data.*.attributes.brand' => 'sometimes|string|nullable',
            'data.*.attributes.manufacturer' => 'sometimes|string|nullable',
            'data.*.attributes.year' => 'sometimes|integer|nullable',
            'data.*.attributes.prefix' => 'sometimes|string|nullable',
            'data.*.attributes.image' => 'sometimes|string|nullable',
            'data.*.attributes.image_thumbnail' => 'sometimes|string|nullable',
            'data.*.attributes.is_subset' => 'sometimes|boolean|nullable',
            'data.*.attributes.is_variation' => 'sometimes|boolean|nullable',
            'data.*.attributes.parent_set_id' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
