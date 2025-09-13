<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Brand
 */
class Brand extends Model
{
    /**
     * Retrieve the sets associated with this brand.
     */
    public function sets(): array
    {
        if (array_key_exists('sets', $this->relationships)) {
            return $this->relationships['sets'];
        }

        return [];
    }
}
