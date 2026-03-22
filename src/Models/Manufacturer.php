<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Manufacturer
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
            return collect($this->relationships['sets']);
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
