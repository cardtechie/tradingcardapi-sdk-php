<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for SetSource API responses
 */
class SetSourceSchema extends BaseSchema
{
    /**
     * Get validation rules for SetSource responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getSetSourceSpecificRules()
        );
    }

    /**
     * Get SetSource-specific validation rules
     */
    private function getSetSourceSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:set-sources,set-source',
            'data.attributes.set_id' => 'sometimes|string|nullable',
            'data.attributes.source_url' => 'sometimes|string|nullable',
            'data.attributes.source_name' => 'sometimes|string|nullable',
            'data.attributes.source_type' => 'sometimes|string|nullable',
            'data.attributes.verified_at' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for SetSource collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getSetSourceCollectionSpecificRules()
        );
    }

    /**
     * Get SetSource collection-specific validation rules
     */
    private function getSetSourceCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:set-sources,set-source',
            'data.*.attributes.set_id' => 'sometimes|string|nullable',
            'data.*.attributes.source_url' => 'sometimes|string|nullable',
            'data.*.attributes.source_name' => 'sometimes|string|nullable',
            'data.*.attributes.source_type' => 'sometimes|string|nullable',
            'data.*.attributes.verified_at' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
