<?php

declare(strict_types=1);

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
            'data.type' => 'required|string|in:set-sources,set_sources,setSources',
            'data.attributes.set_id' => 'required|string',
            'data.attributes.source_type' => 'required|string|in:checklist,metadata,images',
            'data.attributes.source_name' => 'required|string',
            'data.attributes.source_url' => 'sometimes|string|nullable',
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
            'data.*.type' => 'required|string|in:set-sources,set_sources,setSources',
            'data.*.attributes.set_id' => 'required|string',
            'data.*.attributes.source_type' => 'required|string|in:checklist,metadata,images',
            'data.*.attributes.source_name' => 'required|string',
            'data.*.attributes.source_url' => 'sometimes|string|nullable',
            'data.*.attributes.verified_at' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
