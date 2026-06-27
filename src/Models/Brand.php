<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class Brand
 *
 * Represents a card brand in the Trading Card API.
 *
 * @property string $id Brand UUID
 * @property string|null $name Brand name
 * @property string|null $description Brand description
 * @property string|null $logo Logo URL
 * @property string|null $website Website URL
 * @property string|null $headquarters Headquarters location
 * @property int|null $founded Year founded
 * @property string|null $parent_company Parent company name
 * @property bool|null $is_active Whether the brand is active
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
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
