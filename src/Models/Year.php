<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Year
 */
class Year extends Model
{
    /**
     * Retrieve collection of sets associated with this year.
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
     * Check if year has any sets.
     */
    public function hasSets(): bool
    {
        return $this->sets()->isNotEmpty();
    }
}
