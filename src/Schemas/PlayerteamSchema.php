<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Playerteam API responses
 */
class PlayerteamSchema extends BaseSchema
{
    /**
     * Get validation rules for Playerteam responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getPlayerteamSpecificRules()
        );
    }

    /**
     * Get validation rules for Playerteam collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getPlayerteamCollectionSpecificRules()
        );
    }

    /**
     * Get Playerteam-specific validation rules
     */
    private function getPlayerteamSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:playerteams,playerteam',
            'data.attributes.player_id' => 'sometimes|string|nullable',
            'data.attributes.team_id' => 'sometimes|string|nullable',
            'data.attributes.position' => 'sometimes|string|nullable',
            'data.attributes.jersey_number' => 'sometimes|integer|nullable',
            'data.attributes.start_date' => 'sometimes|string|nullable',
            'data.attributes.end_date' => 'sometimes|string|nullable',
            'data.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get Playerteam collection-specific validation rules
     */
    private function getPlayerteamCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:playerteams,playerteam',
            'data.*.attributes.player_id' => 'sometimes|string|nullable',
            'data.*.attributes.team_id' => 'sometimes|string|nullable',
            'data.*.attributes.position' => 'sometimes|string|nullable',
            'data.*.attributes.jersey_number' => 'sometimes|integer|nullable',
            'data.*.attributes.start_date' => 'sometimes|string|nullable',
            'data.*.attributes.end_date' => 'sometimes|string|nullable',
            'data.*.attributes.is_active' => 'sometimes|boolean|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
