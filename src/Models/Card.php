<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Card
 *
 * Represents a trading card in the Trading Card API.
 *
 * @property string $id Card UUID
 * @property string|null $name Card name
 * @property string|null $description Card description
 * @property string|null $rarity Card rarity
 * @property string|null $series Card series
 * @property string|null $brand Brand name
 * @property string|null $manufacturer Manufacturer name
 * @property int|null $year Card year
 * @property string|null $image Image URL
 * @property string|null $image_thumbnail Thumbnail image URL
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 * @property-read string $number Card number with the set prefix stripped (computed)
 * @property-read string $full_number Card number including the set prefix (computed)
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
     * Retrieve collection of objects on the card.
     *
     * @return Collection<int, mixed>
     */
    public function oncard(): Collection
    {
        if (array_key_exists('oncard', $this->relationships)) {
            /** @var iterable<int, mixed> $items */
            $items = $this->relationships['oncard'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if card has any oncard items.
     */
    public function hasOncard(): bool
    {
        return $this->oncard()->isNotEmpty();
    }

    /**
     * Retrieve collection of extra attributes assigned to card.
     *
     * @return Collection<int, mixed>
     */
    public function extraAttributes(): Collection
    {
        if (array_key_exists('attributes', $this->relationships)) {
            /** @var iterable<int, mixed> $items */
            $items = $this->relationships['attributes'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if card has any extra attributes.
     */
    public function hasExtraAttributes(): bool
    {
        return $this->extraAttributes()->isNotEmpty();
    }

    /**
     * Retrieve the set.
     */
    public function set(): ?Set
    {
        return $this->getRelationship('set');
    }

    /**
     * Retrieve collection of card images.
     *
     * @return Collection<int, CardImage>
     */
    public function images(): Collection
    {
        if (array_key_exists('card-images', $this->relationships)) {
            /** @var iterable<int, CardImage> $items */
            $items = $this->relationships['card-images'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if card has any images.
     */
    public function hasImages(): bool
    {
        return $this->images()->isNotEmpty();
    }

    /**
     * Get the front image of the card.
     */
    public function getFrontImage(): ?CardImage
    {
        return $this->images()
            ->firstWhere('image_type', 'front');
    }

    /**
     * Get the back image of the card.
     */
    public function getBackImage(): ?CardImage
    {
        return $this->images()
            ->firstWhere('image_type', 'back');
    }

    /**
     * Override the parent class implementation to correctly place all relationships.
     *
     * @param  array<string, mixed>  $relationships
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

        if (array_key_exists('team', $this->relationships) && count($this->relationships['team']) === 0) {
            unset($this->relationships['team']);
        }
        if (array_key_exists('player', $this->relationships) && count($this->relationships['player']) === 0) {
            unset($this->relationships['player']);
        }
        if (array_key_exists('playerteam', $this->relationships) && count($this->relationships['playerteam']) === 0) {
            unset($this->relationships['playerteam']);
        }
    }
}
