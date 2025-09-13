<?php

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class Year
 */
class Year extends Model
{
    /**
     * Retrieve the sets associated with this year.
     */
    public function sets(): array
    {
        if (array_key_exists('sets', $this->relationships)) {
            return $this->relationships['sets'];
        }

        return [];
    }
}
