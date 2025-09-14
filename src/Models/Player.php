<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;

/**
 * Class Player
 *
 * @property string|null $first_name
 * @property string|null $last_name
 */
class Player extends Model implements Taxonomy
{
    /**
     * Return the full name of the player
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Build the taxonomy object
     */
    public static function build(object $taxonomy, array $data): object
    {
        // Find the matching player in the data array
        if (isset($data['player']) && is_array($data['player'])) {
            foreach ($data['player'] as $playerData) {
                if (isset($playerData->id) && $playerData->id === $taxonomy->id) {
                    // Set the player data as a relationship on the taxonomy object
                    if (! isset($taxonomy->relationships)) {
                        $taxonomy->relationships = [];
                    }
                    $taxonomy->relationships['player'] = $playerData;
                    break;
                }
            }
        }

        // If we have player data directly, use it
        if (isset($data['player']) && is_object($data['player']) && isset($data['player']->id)) {
            if ($data['player']->id === $taxonomy->id) {
                if (! isset($taxonomy->relationships)) {
                    $taxonomy->relationships = [];
                }
                $taxonomy->relationships['player'] = $data['player'];
            }
        }

        return $taxonomy;
    }

    /**
     * Get the object from the API
     */
    public static function getFromApi(array $params): object
    {
        $player = TradingCardApiSdk::player()->getList([
            'full_name' => $params['player'],
        ]);

        if ($player->isEmpty()) {
            $player = TradingCardApiSdk::player()->create([
                'full_name' => $params['player'],
            ]);
        }

        return $player->first();
    }
}
