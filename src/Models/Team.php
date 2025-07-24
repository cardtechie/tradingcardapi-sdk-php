<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

/**
 * Class Team
 *
 * @property string $location
 * @property string $mascot
 */
class Team extends Model implements Taxonomy
{
    /**
     * Get the full name of the team
     */
    public function getNameAttribute(): ?string
    {
        return $this->location.' '.$this->mascot;
    }

    // phpcs:disable
    /**
     * Build the taxonomy object
     */
    public static function build(object $taxonomy, array $data): object
    {
        // TODO: Implement build() method.
        return new \stdClass;
    }
    // phpcs:enable

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
