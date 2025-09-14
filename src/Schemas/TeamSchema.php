<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Team API responses
 */
class TeamSchema extends BaseSchema
{
    /**
     * Get validation rules for Team responses
     */
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            $this->getTeamSpecificRules()
        );
    }

    /**
     * Get Team-specific validation rules
     */
    private function getTeamSpecificRules(): array
    {
        return [
            'data.type' => 'required|string|in:teams,team',
            'data.attributes.name' => 'sometimes|string|nullable',
            'data.attributes.location' => 'sometimes|string|nullable',
            'data.attributes.mascot' => 'sometimes|string|nullable',
            'data.attributes.abbreviation' => 'sometimes|string|nullable',
            'data.attributes.league' => 'sometimes|string|nullable',
            'data.attributes.division' => 'sometimes|string|nullable',
            'data.attributes.conference' => 'sometimes|string|nullable',
            'data.attributes.founded' => 'sometimes|integer|nullable',
            'data.attributes.city' => 'sometimes|string|nullable',
            'data.attributes.state' => 'sometimes|string|nullable',
            'data.attributes.country' => 'sometimes|string|nullable',
            'data.attributes.logo' => 'sometimes|string|nullable',
            'data.attributes.created_at' => 'sometimes|string|nullable',
            'data.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }

    /**
     * Get validation rules for Team collection responses
     */
    public function getCollectionRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiCollectionRules(),
            $this->getMetaLinksRules(),
            $this->getTeamCollectionSpecificRules()
        );
    }

    /**
     * Get Team collection-specific validation rules
     */
    private function getTeamCollectionSpecificRules(): array
    {
        return [
            'data.*.type' => 'required|string|in:teams,team',
            'data.*.attributes.name' => 'sometimes|string|nullable',
            'data.*.attributes.location' => 'sometimes|string|nullable',
            'data.*.attributes.mascot' => 'sometimes|string|nullable',
            'data.*.attributes.abbreviation' => 'sometimes|string|nullable',
            'data.*.attributes.league' => 'sometimes|string|nullable',
            'data.*.attributes.division' => 'sometimes|string|nullable',
            'data.*.attributes.conference' => 'sometimes|string|nullable',
            'data.*.attributes.founded' => 'sometimes|integer|nullable',
            'data.*.attributes.city' => 'sometimes|string|nullable',
            'data.*.attributes.state' => 'sometimes|string|nullable',
            'data.*.attributes.country' => 'sometimes|string|nullable',
            'data.*.attributes.logo' => 'sometimes|string|nullable',
            'data.*.attributes.created_at' => 'sometimes|string|nullable',
            'data.*.attributes.updated_at' => 'sometimes|string|nullable',
        ];
    }
}
