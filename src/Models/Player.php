<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApi;

/**
 * Class Player
 */
class Player extends Model implements Taxonomy
{
    /**
     * Return the full name of the player
     *
     * @return string
     */
    public function getFullNameAttribute() : ?string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // phpcs:disable
    /**
     * Build the taxonomy object
     *
     * @param Object $taxonomy
     * @param array $data
     */
    public static function build(object $taxonomy, array $data): object
    {
        // TODO: Implement build() method.
    }
    // phpcs:enable

    /**
     * Get the object from the API
     *
     * @param array $params
     *
     * @return Object
     */
    public static function getFromApi(array $params) : Object
    {
        $player = TradingCardApi::player()->getList([
            'full_name' => $params['player'],
        ]);

        if ($player->isEmpty()) {
            $player = TradingCardApi::player()->create([
                'full_name' => $params['player'],
            ]);
        }

        return $player->first();
    }
}
