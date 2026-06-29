<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Manufacturer API responses
 */
class ManufacturerSchema extends BaseSchema
{
    /**
     * Get validation rules for Manufacturer responses
     *
     * @return array<string, mixed>
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
     * Get validation rules for Manufacturer collection responses
     *
     * @return array<string, mixed>
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getManufacturerCollectionSpecificRules()
        );
    }

    /**
     * Get Manufacturer-specific validation rules
     *
     * @return array<string, mixed>
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

    /**
     * Get Manufacturer-specific validation rules for collections
     *
     * @return array<string, mixed>
     */
    private function getManufacturerCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'sometimes|required|string|in:manufacturers,manufacturer',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.website' => 'sometimes|string|nullable',
            'data.*.attributes.founded' => 'sometimes|integer|nullable',
            'data.*.attributes.headquarters' => 'sometimes|string|nullable',
            'data.*.attributes.country' => 'sometimes|string|nullable',
            'data.*.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
