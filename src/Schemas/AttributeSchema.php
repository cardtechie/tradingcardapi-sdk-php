<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Attribute API responses
 */
class AttributeSchema extends BaseSchema
{
    /**
     * Get validation rules for Attribute responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getAttributeSpecificRules()
        );
    }

    /**
     * Get Attribute-specific validation rules
     */
    private function getAttributeSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:attributes,attribute',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.value' => 'sometimes|string|nullable',
            'data.attributes.type' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.is_searchable' => 'sometimes|boolean|nullable',
            'data.attributes.is_filterable' => 'sometimes|boolean|nullable',
            'data.attributes.sort_order' => 'sometimes|integer|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Attribute collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getAttributeCollectionSpecificRules()
        );
    }

    /**
     * Get Attribute collection-specific validation rules
     */
    private function getAttributeCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:attributes,attribute',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.value' => 'sometimes|string|nullable',
            'data.*.attributes.type' => 'sometimes|string|nullable',
            'data.*.attributes.description' => 'sometimes|string|nullable',
            'data.*.attributes.is_searchable' => 'sometimes|boolean|nullable',
            'data.*.attributes.is_filterable' => 'sometimes|boolean|nullable',
            'data.*.attributes.sort_order' => 'sometimes|integer|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
