<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Manufacturer API responses
 */
class ManufacturerSchema extends BaseSchema
{
    /**
     * Get validation rules for Manufacturer responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getManufacturerSpecificRules()
        );
    }

    /**
     * Get Manufacturer-specific validation rules
     */
    private function getManufacturerSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:manufacturers,manufacturer',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.website' => 'sometimes|string|nullable',
            'data.attributes.founded' => 'sometimes|integer|nullable',
            'data.attributes.headquarters' => 'sometimes|string|nullable',
            'data.attributes.country' => 'sometimes|string|nullable',
            'data.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
