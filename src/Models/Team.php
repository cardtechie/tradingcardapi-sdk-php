<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\Models\Traits\OnCardable;
use CardTechie\TradingCardApiSdk\Utils\StringHelpers;

/**
 * Class Team
 *
 * @property string $location
 * @property string $mascot
 */
class Team extends Model implements Taxonomy
{
    use OnCardable;

    /**
     * Return the onCardable configuration array for this model.
     */
    public function onCardable(): array
    {
        return [
            'name' => 'Team',
        ];
    }

    /**
     * Prepare the on card relationships and return the object that matches the passed in data.
     *
     * @param  array<string, mixed>  $data  Array containing 'team' key with UUID or name
     * @return Team|null
     */
    public static function prepare($data): ?object
    {
        if (empty($data['team'])) {
            return null;
        }

        /** @var string $teamValue */
        $teamValue = $data['team'];

        // If it's a UUID, validate and return a team instance
        if (StringHelpers::isValidUuid($teamValue)) {
            try {
                return TradingCardApiSdk::team()->get($teamValue);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Team with UUID {$teamValue} not found", 0, $e);
            }
        }

        // It's a name, look up or create the team
        return self::getFromApi(['team' => $teamValue]);
    }

    /**
     * Get the full name of the team
     */
    public function getNameAttribute(): ?string
    {
        return $this->location.' '.$this->mascot;
    }

    /**
     * Build the taxonomy object
     */
    public static function build(object $taxonomy, array $data): object
    {
        // Find the matching team in the data array
        if (isset($data['team']) && is_array($data['team'])) {
            foreach ($data['team'] as $teamData) {
                if (isset($teamData->id) && $teamData->id === $taxonomy->id) {
                    // Set the team data as a relationship on the taxonomy object
                    if (! isset($taxonomy->relationships)) {
                        $taxonomy->relationships = [];
                    }
                    $taxonomy->relationships['team'] = $teamData;
                    break;
                }
            }
        }

        // If we have team data directly, use it
        if (isset($data['team']) && is_object($data['team']) && isset($data['team']->id)) {
            if ($data['team']->id === $taxonomy->id) {
                if (! isset($taxonomy->relationships)) {
                    $taxonomy->relationships = [];
                }
                $taxonomy->relationships['team'] = $data['team'];
            }
        }

        return $taxonomy;
    }

    /**
     * Get the object from the API
     */
    public static function getFromApi(array $params): object
    {
        $teams = TradingCardApiSdk::team()->getList([
            'name' => $params['team'],
            'limit' => 50,
        ]);

        if ($teams->isEmpty()) {
            $team = TradingCardApiSdk::team()->create([
                'name' => $params['team'],
            ]);

            return $team;
        }

        $selectedTeam = null;
        if ($teams->count() === 1) {
            $selectedTeam = $teams->first();
        } else {
            foreach ($teams as $team) {
                if (trim($team->name) == $params['team']) {
                    $selectedTeam = $team;
                    break;
                }
            }
        }

        if (is_null($selectedTeam)) {
            $team = TradingCardApiSdk::team()->create([
                'name' => $params['team'],
            ]);

            return $team;
        }

        return $selectedTeam;
    }
}
