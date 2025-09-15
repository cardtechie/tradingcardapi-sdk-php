<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Card API responses
 */
class CardSchema extends BaseSchema
{
    /**
     * Get validation rules for Card responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getCardSpecificRules()
        );
    }

    /**
     * Get Card-specific validation rules
     */
    private function getCardSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:cards,card',
            'data.attributes.number' => 'sometimes|string|nullable',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.image' => 'sometimes|string|nullable',
            'data.attributes.image_thumbnail' => 'sometimes|string|nullable',
            'data.attributes.rarity' => 'sometimes|string|nullable',
            'data.attributes.series' => 'sometimes|string|nullable',
            'data.attributes.brand' => 'sometimes|string|nullable',
            'data.attributes.manufacturer' => 'sometimes|string|nullable',
            'data.attributes.year' => 'sometimes|integer|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Card collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getCardCollectionSpecificRules()
        );
    }

    /**
     * Get Card collection-specific validation rules
     */
    private function getCardCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:cards,card',
            'data.*.attributes.number' => 'sometimes|string|nullable',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.image' => 'sometimes|string|nullable',
            'data.*.attributes.image_thumbnail' => 'sometimes|string|nullable',
            'data.*.attributes.rarity' => 'sometimes|string|nullable',
            'data.*.attributes.series' => 'sometimes|string|nullable',
            'data.*.attributes.brand' => 'sometimes|string|nullable',
            'data.*.attributes.manufacturer' => 'sometimes|string|nullable',
            'data.*.attributes.year' => 'sometimes|integer|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
