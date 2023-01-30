<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

/**
 * Class Team
 */
class Team extends Model implements Taxonomy
{
    /**
     * Get the full name of the team
     *
     * @return string
     */
    public function getNameAttribute(): ?string
    {
        return $this->location.' '.$this->mascot;
    }

    // phpcs:disable
    /**
     * Build the taxonomy object
     *
     * @param  object  $taxonomy
     * @param  array  $data
     */
    public static function build(object $taxonomy, array $data): object
    {
        // TODO: Implement build() method.
    }
    // phpcs:enable

    /**
     * Get the object from the API
     *
     * @param  array  $params
     * @return object
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

            return $team->first();
        }

        $selectedTeam = null;
        if (1 === $teams->count()) {
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

            return $team->first();
        }

        return $selectedTeam;
    }
}
