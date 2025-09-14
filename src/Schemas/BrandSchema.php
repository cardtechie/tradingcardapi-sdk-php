<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Brand API responses
 */
class BrandSchema extends BaseSchema
{
    /**
     * Get validation rules for Brand responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getBrandSpecificRules()
        );
    }

    /**
     * Get Brand-specific validation rules
     */
    private function getBrandSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:brands,brand',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.website' => 'sometimes|string|nullable',
            'data.attributes.logo' => 'sometimes|string|nullable',
            'data.attributes.founded' => 'sometimes|integer|nullable',
            'data.attributes.headquarters' => 'sometimes|string|nullable',
            'data.attributes.parent_company' => 'sometimes|string|nullable',
            'data.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Brand collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getBrandCollectionSpecificRules()
        );
    }

    /**
     * Get Brand collection-specific validation rules
     */
    private function getBrandCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:brands,brand',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.website' => 'sometimes|string|nullable',
            'data.*.attributes.logo' => 'sometimes|string|nullable',
            'data.*.attributes.founded' => 'sometimes|integer|nullable',
            'data.*.attributes.headquarters' => 'sometimes|string|nullable',
            'data.*.attributes.parent_company' => 'sometimes|string|nullable',
            'data.*.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
