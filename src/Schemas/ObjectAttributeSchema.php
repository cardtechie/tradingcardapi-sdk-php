<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for ObjectAttribute API responses
 */
class ObjectAttributeSchema extends BaseSchema
{
    /**
     * Get validation rules for ObjectAttribute responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getObjectAttributeSpecificRules()
        );
    }

    /**
     * Get ObjectAttribute-specific validation rules
     */
    private function getObjectAttributeSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:object-attributes,objectattributes,object_attributes',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.value' => 'sometimes|string|nullable',
            'data.attributes.type' => 'sometimes|string|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.object_type' => 'sometimes|string|nullable',
            'data.attributes.object_id' => 'sometimes|string|nullable',
            'data.attributes.is_public' => 'sometimes|boolean|nullable',
            'data.attributes.is_searchable' => 'sometimes|boolean|nullable',
            'data.attributes.sort_order' => 'sometimes|integer|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
