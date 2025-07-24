<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Card
 * 
 * @property string $id
 */
class Card extends Model
{
    /**
     * Format the number
     */
    public function getNumberAttribute(): string
    {
        $parentSet = $this->set();
        if ($parentSet && $prefix = $parentSet->number_prefix) {
            $number = $this->attributes['number'];

            return substr($number, strlen($prefix));
        }

        return $this->attributes['number'];
    }

    /**
     * Get the number of the card unformatted
     */
    public function getFullNumberAttribute(): string
    {
        return $this->attributes['number'];
    }

    /**
     * Retrieve array of objects on the card.
     */
    public function oncard(): ?array
    {
        $key = 'oncard';
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        return [];
    }

    /**
     * Retrieve array of extra attributes assigned to card
     *
     * @return array
     */
    public function extraAttributes(): ?array
    {
        $key = 'attributes';
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        return [];
    }

    /**
     * Retrieve the set.
     */
    public function set(): ?Set
    {
        return $this->getRelationship('set');
    }

    /**
     * Override the parent class implementation to correctly place all relationships.
     */
    public function setRelationships(array $relationships): void
    {
        parent::setRelationships($relationships);

        if (array_key_exists('playerteam', $this->relationships)) {
            foreach ($this->relationships['playerteam'] as $playerTeam) {
                $ptRelationships = [];
                if (array_key_exists('team', $this->relationships)) {
                    $teamId = $playerTeam->team_id;
                    foreach ($this->relationships['team'] as $index => $team) {
                        if ($teamId === $team->id) {
                            $ptRelationships['team'] = $this->relationships['team'][$index];
                        }
                    }
                }
                if (array_key_exists('player', $this->relationships)) {
                    $playerId = $playerTeam->player_id;
                    foreach ($this->relationships['player'] as $index => $player) {
                        if ($playerId === $player->id) {
                            $ptRelationships['player'] = $this->relationships['player'][$index];
                        }
                    }
                }
                $playerTeam->setRelationships($ptRelationships);
            }
        }

        if (array_key_exists('oncard', $this->relationships)) {
            foreach ($this->relationships['oncard'] as $onCard) {
                $oncardObjects = [];
                $type = $onCard->on_cardable_type;
                $modelId = $onCard->on_cardable_id;

                if (array_key_exists($type, $this->relationships)) {
                    foreach ($this->relationships[$type] as $index => $model) {
                        if ($modelId === $model->id) {
                            $oncardObjects[$type] = $this->relationships[$type][$index];
                        }
                    }
                }
                $onCard->setRelationships($oncardObjects);
            }
        }

        if (array_key_exists('team', $this->relationships) && 0 === count($this->relationships['team'])) {
            unset($this->relationships['team']);
        }
        if (array_key_exists('player', $this->relationships) && 0 === count($this->relationships['player'])) {
            unset($this->relationships['player']);
        }
        if (array_key_exists('playerteam', $this->relationships) && 0 === count($this->relationships['playerteam'])) {
            unset($this->relationships['playerteam']);
        }
    }
}
