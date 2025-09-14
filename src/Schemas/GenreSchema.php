<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Genre API responses
 */
class GenreSchema extends BaseSchema
{
    /**
     * Get validation rules for Genre responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getGenreSpecificRules()
        );
    }

    /**
     * Get Genre-specific validation rules
     */
    private function getGenreSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:genres,genre',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.slug' => 'sometimes|string|nullable',
            'data.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.attributes.sort_order' => 'sometimes|integer|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
            'data.attributes.deleted_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Genre collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getGenreCollectionSpecificRules()
        );
    }

    /**
     * Get Genre collection-specific validation rules
     */
    private function getGenreCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:genres,genre',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.slug' => 'sometimes|string|nullable',
            'data.*.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.*.attributes.sort_order' => 'sometimes|integer|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
            'data.*.attributes.deleted_at' => 'sometimes|string|nullable',
        ];
    }
}
