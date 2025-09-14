<?php

namespace CardTechie\TradingCardApiSdk\Schemas;

/**
 * Schema for Stats API responses
 */
class StatsSchema extends BaseSchema
{
    /**
     * Get validation rules for Stats responses
     * Note: Stats responses typically don't follow JSON:API format
     */
    public function getRules(): array
    {
        return [
            'total' => 'sometimes|integer',
            'count' => 'sometimes|integer',
            'active' => 'sometimes|integer',
            'inactive' => 'sometimes|integer',
            'published' => 'sometimes|integer',
            'unpublished' => 'sometimes|integer',
            'created_today' => 'sometimes|integer',
            'created_this_week' => 'sometimes|integer',
            'created_this_month' => 'sometimes|integer',
            'created_this_year' => 'sometimes|integer',
            'updated_today' => 'sometimes|integer',
            'updated_this_week' => 'sometimes|integer',
            'updated_this_month' => 'sometimes|integer',
            'updated_this_year' => 'sometimes|integer',
        ];
    }

    /**
     * Get validation rules for specific resource type stats
     */
    public function getResourceStatsRules(string $resourceType): array
    {
        $baseRules = $this->getRules();

        // Add resource-specific stats rules
        switch ($resourceType) {
            case 'cards':
                return array_merge($baseRules, [
                    'by_rarity' => 'sometimes|array',
                    'by_series' => 'sometimes|array',
                    'by_year' => 'sometimes|array',
                ]);

            case 'sets':
                return array_merge($baseRules, [
                    'by_brand' => 'sometimes|array',
                    'by_manufacturer' => 'sometimes|array',
                    'by_year' => 'sometimes|array',
                ]);

            case 'players':
                return array_merge($baseRules, [
                    'by_position' => 'sometimes|array',
                    'by_team' => 'sometimes|array',
                    'by_nationality' => 'sometimes|array',
                ]);

            case 'teams':
                return array_merge($baseRules, [
                    'by_league' => 'sometimes|array',
                    'by_division' => 'sometimes|array',
                    'by_country' => 'sometimes|array',
                ]);

            default:
                return $baseRules;
        }
    }
}
