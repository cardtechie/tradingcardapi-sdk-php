<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Manufacturer
 *
 * Represents a card manufacturer in the Trading Card API.
 *
 * @property string $id Manufacturer UUID
 * @property string|null $name Manufacturer name
 * @property string|null $description Manufacturer description
 * @property string|null $country Country
 * @property string|null $headquarters Headquarters location
 * @property string|null $website Website URL
 * @property int|null $founded Year founded
 * @property bool|null $is_active Whether the manufacturer is active
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class Manufacturer extends Model
{
    /**
     * Retrieve collection of sets associated with this manufacturer.
     *
     * @return Collection<int, Set>
     */
    public function sets(): Collection
    {
        if (array_key_exists('sets', $this->relationships)) {
            /** @var iterable<int, Set> $items */
            $items = $this->relationships['sets'];

            return collect($items);
        }

        return collect([]);
    }

    /**
     * Check if manufacturer has any sets.
     */
    public function hasSets(): bool
    {
        return $this->sets()->isNotEmpty();
    }
}
