<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class SetSource
 *
 * Represents a source of data for a trading card set (checklist, metadata, or images).
 *
 * @property string $id
 * @property string $set_id
 * @property string $source_url
 * @property string|null $source_name
 * @property string $source_type
 * @property string|null $verified_at
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SetSource extends Model
{
    /**
     * Retrieve the set this source belongs to.
     */
    public function set(): ?Set
    {
        return $this->getRelationship('set');
    }
}
