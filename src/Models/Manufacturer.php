<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Manufacturer
 */
class Manufacturer extends Model
{
    /**
     * Retrieve the sets associated with this manufacturer.
     */
    public function sets(): array
    {
        if (array_key_exists('sets', $this->relationships)) {
            return $this->relationships['sets'];
        }

        return [];
    }
}
