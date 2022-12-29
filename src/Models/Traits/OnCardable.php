<?php

namespace CardTechie\TradingCardApiSdk\Models\Traits;

/**
 * Trait OnCardable
 */
trait OnCardable
{
    /**
     * Return the onCardable configuration array for this model.
     */
    abstract public function onCardable() : array;

    /**
     * Prepare the on card relationships and return the object that matches the passed in data.
     *
     * @param array $data
     *
     * @return string
     */
    abstract public static function prepare($data) : ?Object;
}
