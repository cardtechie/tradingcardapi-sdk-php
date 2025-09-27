<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use Illuminate\Support\Collection;

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
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Return the last name first format (for display purposes)
     */
    public function getLastNameFirstAttribute(): string
    {
        $name = [];

        if (!empty($this->last_name)) {
            $name[] = $this->last_name;
        }

        if (!empty($this->first_name)) {
            $name[] = $this->first_name;
        }

        return implode(', ', $name);
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

    /**
     * Get the parent player (if this is an alias)
     *
     * @return Player|null The parent player or null if this is not an alias
     */
    public function getParent(): ?Player
    {
        if (!isset($this->parent_id) || empty($this->parent_id)) {
            return null;
        }

        try {
            return TradingCardApiSdk::player()->get($this->parent_id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all aliases (child players) of this player
     *
     * @return Collection Collection of Player models that are aliases of this player
     */
    public function getAliases(): Collection
    {
        try {
            return TradingCardApiSdk::player()->getList([
                'parent_id' => $this->id,
            ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get all teams this player has been associated with
     *
     * @return Collection Collection of Team models
     */
    public function getTeams(): Collection
    {
        try {
            $playerteams = TradingCardApiSdk::playerteam()->getList([
                'player_id' => $this->id,
                'include' => 'team',
            ]);

            return $playerteams->map(function ($playerteam) {
                return $playerteam->team();
            })->filter()->values();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get all playerteam relationships for this player
     *
     * @return Collection Collection of Playerteam models
     */
    public function getPlayerteams(): Collection
    {
        try {
            return TradingCardApiSdk::playerteam()->getList([
                'player_id' => $this->id,
            ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get all cards featuring this player
     * Note: This method will need to be implemented once Card relationships are available
     *
     * @return Collection Collection of Card models
     */
    public function getCards(): Collection
    {
        // TODO: Implement when Card-Player relationship endpoints are available
        // This would likely involve querying cards with player_id or through OnCard relationships
        return collect();
    }

    /**
     * Check if this player is an alias (has a parent)
     *
     * @return bool True if this player is an alias, false otherwise
     */
    public function isAlias(): bool
    {
        return isset($this->parent_id) && !empty($this->parent_id);
    }

    /**
     * Check if this player has aliases (child players)
     *
     * @return bool True if this player has aliases, false otherwise
     */
    public function hasAliases(): bool
    {
        return $this->getAliases()->isNotEmpty();
    }
}
