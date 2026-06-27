<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;

/**
 * Class ObjectAttribute
 *
 * Represents an attribute attached to a specific object (card or set) in the Trading Card API.
 *
 * @property string $id Object attribute UUID
 * @property string|null $name Attribute name
 * @property string|null $value Attribute value
 * @property string|null $type Data type (boolean|integer|string)
 * @property string|null $description Attribute description
 * @property string|null $object_id UUID of the related object
 * @property string|null $object_type Type of the related object (card|set)
 * @property bool|null $is_searchable Whether the attribute is searchable
 * @property bool|null $is_public Whether the attribute is public
 * @property int|null $sort_order Sort order
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
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
