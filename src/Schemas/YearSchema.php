<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Year API responses
 */
class YearSchema extends BaseSchema
{
    /**
     * Get validation rules for Year responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getYearSpecificRules()
        );
    }

    /**
     * Get Year-specific validation rules
     */
    private function getYearSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:years,year',
            'data.attributes.year' => 'sometimes|integer|nullable',
            'data.attributes.description' => 'sometimes|string|nullable',
            'data.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.attributes.card_count' => 'sometimes|integer|nullable',
            'data.attributes.set_count' => 'sometimes|integer|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
