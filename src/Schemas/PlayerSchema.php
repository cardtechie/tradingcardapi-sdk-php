<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Player API responses
 */
class PlayerSchema extends BaseSchema
{
    /**
     * Get validation rules for Player responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getPlayerSpecificRules()
        );
    }

    /**
     * Get Player-specific validation rules
     */
    private function getPlayerSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:players,player',
            'data.attributes.first_name' => 'sometimes|string|nullable',
            'data.attributes.last_name' => 'sometimes|string|nullable',
            'data.attributes.full_name' => 'sometimes|string|nullable',
            'data.attributes.position' => 'sometimes|string|nullable',
            'data.attributes.team' => 'sometimes|string|nullable',
            'data.attributes.jersey_number' => 'sometimes|integer|nullable',
            'data.attributes.birthdate' => 'sometimes|string|nullable',
            'data.attributes.height' => 'sometimes|string|nullable',
            'data.attributes.weight' => 'sometimes|string|nullable',
            'data.attributes.nationality' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Player collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getPlayerCollectionSpecificRules()
        );
    }

    /**
     * Get Player collection-specific validation rules
     */
    private function getPlayerCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:players,player',
            'data.*.attributes.first_name' => 'sometimes|string|nullable',
            'data.*.attributes.last_name' => 'sometimes|string|nullable',
            'data.*.attributes.full_name' => 'sometimes|string|nullable',
            'data.*.attributes.position' => 'sometimes|string|nullable',
            'data.*.attributes.team' => 'sometimes|string|nullable',
            'data.*.attributes.jersey_number' => 'sometimes|integer|nullable',
            'data.*.attributes.birthdate' => 'sometimes|string|nullable',
            'data.*.attributes.height' => 'sometimes|string|nullable',
            'data.*.attributes.weight' => 'sometimes|string|nullable',
            'data.*.attributes.nationality' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
