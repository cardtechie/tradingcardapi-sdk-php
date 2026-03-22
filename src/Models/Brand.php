<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Brand
 */
class Brand extends Model
{
    /**
     * Retrieve collection of sets associated with this brand.
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
     * Check if brand has any sets.
     */
    public function hasSets(): bool
    {
        return $this->sets()->isNotEmpty();
    }
}
