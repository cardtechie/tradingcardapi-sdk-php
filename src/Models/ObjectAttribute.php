<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

class ObjectAttribute extends Model
{
    /**
     * Retrieve collection of cards with this attribute.
     *
     * @return Collection<int, Card>
     */
    public function cards(): Collection
    {
        $relationships = $this->getRelationships();

        if (array_key_exists('cards', $relationships)) {
            return collect($relationships['cards']);
        }

        return collect([]);
    }

    /**
     * Check if attribute has any cards.
     */
    public function hasCards(): bool
    {
        return $this->cards()->isNotEmpty();
    }
}
