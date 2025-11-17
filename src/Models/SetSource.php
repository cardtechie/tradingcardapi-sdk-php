<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Carbon\Carbon;

/**
 * Class SetSource
 *
 * Represents a set source in the Trading Card API for tracking data provenance.
 *
 * @property string $id Set source UUID
 * @property string $set_id Related set UUID
 * @property string $source_type Source type (checklist|metadata|images)
 * @property string $source_name Name of the data source
 * @property string|null $source_url URL of the data source
 * @property Carbon|null $verified_at Timestamp when source was last verified
 * @property Carbon $created_at Creation timestamp
 * @property Carbon $updated_at Last update timestamp
 */
class SetSource extends Model
{
    /**
     * Get the related set.
     */
    public function set(): ?Set
    {
        return $this->getRelationship('set');
    }

    /**
     * Get the verified_at attribute as Carbon instance.
     */
    public function getVerifiedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['verified_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['verified_at']);
    }

    /**
     * Get the created_at attribute as Carbon instance.
     */
    public function getCreatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['created_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['created_at']);
    }

    /**
     * Get the updated_at attribute as Carbon instance.
     */
    public function getUpdatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['updated_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['updated_at']);
    }
}
